<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\MediaUpload;
use App\Models\Post;
use App\Models\PostBlock;
use App\Support\Media;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/** Publishing: write, schedule and manage Insights posts (studies, guides, updates, spotlights, articles). */
class PostController extends Controller
{
    public function index(Request $request)
    {
        $f = $request->validate([
            'status' => 'nullable|in:published,scheduled,draft,archived',
            'type' => 'nullable|in:'.implode(',', array_keys(Post::TYPES)),
            'q' => 'nullable|string|max:120',
            'sort' => 'nullable|in:updated,published,views,title',
        ]);
        $base = fn () => Post::query()
            ->when($f['type'] ?? null, fn ($q, $t) => $q->where('type', $t))
            ->when($f['q'] ?? null, fn ($q, $t) => $q->where(fn ($w) => $w->where('title', 'like', "%{$t}%")->orWhere('excerpt', 'like', "%{$t}%")->orWhere('slug', 'like', "%{$t}%")));
        $byState = fn ($q, $s) => match ($s) {
            'published' => $q->public(),
            'scheduled' => $q->where('status', 'published')->where('published_at', '>', now()),
            default => $q->where('status', $s),
        };

        $posts = $base()->with('author')->withCount('blocks')
            ->when($f['status'] ?? null, $byState)
            ->tap(fn ($q) => match ($f['sort'] ?? 'updated') {
                'published' => $q->orderByDesc('published_at'),
                'views' => $q->orderByDesc('views'),
                'title' => $q->orderBy('title'),
                default => $q->latest('updated_at'),
            })
            ->paginate(20)->withQueryString();

        $counts = ['' => $base()->count()];
        foreach (['published', 'scheduled', 'draft', 'archived'] as $s) {
            $counts[$s] = $byState($base(), $s)->count();
        }
        $totals = [
            'views' => (int) Post::public()->sum('views'),
            'published' => Post::public()->count(),
            'month' => Post::public()->where('published_at', '>=', now()->subDays(30))->count(),
        ];

        return view('admin.posts.index', compact('posts', 'f', 'counts', 'totals'));
    }

    public function create()
    {
        $post = new Post(['type' => 'article', 'audience' => 'everyone', 'settings' => Post::cleanSettings([])]);
        $post->setRelation('blocks', collect());

        return view('admin.posts.form', ['post' => $post]);
    }

    public function edit(Post $post)
    {
        $post->load('blocks', 'author');

        return view('admin.posts.form', ['post' => $post]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request, null);
        $post = DB::transaction(function () use ($data, $request) {
            $post = new Post($data['attributes']);
            $post->slug = Post::uniqueSlug($data['slug'] ?: $post->title);
            $post->author_id = $request->user()->id;
            $post->updated_by = $request->user()->id;
            $post->status = 'draft';
            $post->cover_path = $data['cover_upload'];
            $post->save();
            $this->syncBlocks($post, $data['blocks'], $request);

            return $post;
        });
        AuditLog::record('post.created', $post, null, ['title' => $post->title]);
        $message = $this->applyIntent($post, $data);

        return redirect()->route('admin.posts.edit', $post)->with('status', $message);
    }

    public function update(Request $request, Post $post)
    {
        $data = $this->validated($request, $post);
        DB::transaction(function () use ($post, $data, $request) {
            $post->fill($data['attributes']);
            if ($data['slug'] && $data['slug'] !== $post->slug) {
                $post->slug = Post::uniqueSlug($data['slug'], $post->id);
            }
            if ($data['cover_upload']) {
                $old = $post->cover_path;
                $post->cover_path = $data['cover_upload'];
                $this->discard($old, $post);
            } elseif ($data['remove_cover'] && $post->cover_path) {
                $old = $post->cover_path;
                $post->cover_path = null;
                $this->discard($old, $post);
            }
            $post->updated_by = $request->user()->id;
            $post->save();
            $this->syncBlocks($post, $data['blocks'], $request);
        });
        AuditLog::record('post.updated', $post, null, ['title' => $post->title]);
        $message = $this->applyIntent($post, $data);

        return redirect()->route('admin.posts.edit', $post)->with('status', $message);
    }

    /** Publish / unpublish / archive / restore / pin, from the list or the editor. */
    public function status(Request $request, Post $post)
    {
        $action = $request->validate(['action' => 'required|in:publish,unpublish,archive,restore,feature,unfeature'])['action'];
        match ($action) {
            'publish' => $post->forceFill(['status' => 'published', 'published_at' => $post->published_at && $post->published_at->isFuture() ? $post->published_at : ($post->published_at ?? now())])->save(),
            'unpublish', 'restore' => $post->forceFill(['status' => 'draft'])->save(),
            'archive' => $post->forceFill(['status' => 'archived', 'featured_at' => null])->save(),
            'feature' => $post->forceFill(['featured_at' => now()])->save(),
            'unfeature' => $post->forceFill(['featured_at' => null])->save(),
        };
        $log = ['publish' => 'published', 'unpublish' => 'unpublished', 'restore' => 'unpublished', 'archive' => 'archived', 'feature' => 'featured', 'unfeature' => 'unfeatured'][$action];
        AuditLog::record('post.'.$log, $post, null, ['title' => $post->title]);

        return back()->with('status', [
            'publish' => $post->isScheduled() ? 'Scheduled for '.$post->published_at->format('M j, g:ia').'.' : 'Published — it is live on Insights.',
            'unpublish' => 'Unpublished. It is a draft again.', 'restore' => 'Restored as a draft.', 'archive' => 'Archived. It no longer appears on Insights.',
            'feature' => 'Pinned to the top of Insights.', 'unfeature' => 'Unpinned.',
        ][$action]);
    }

    public function duplicate(Request $request, Post $post)
    {
        $copy = DB::transaction(function () use ($post, $request) {
            $copy = $post->replicate(['slug', 'views', 'published_at', 'featured_at', 'status']);
            $copy->title = Str::limit($post->title, 170, '').' (copy)';
            $copy->slug = Post::uniqueSlug($copy->title);
            $copy->status = 'draft';
            $copy->views = 0;
            $copy->author_id = $request->user()->id;
            $copy->updated_by = $request->user()->id;
            $copy->save();
            foreach ($post->blocks as $b) {
                $copy->blocks()->create($b->replicate(['post_id'])->toArray());
            }

            return $copy;
        });
        AuditLog::record('post.duplicated', $copy, null, ['from' => $post->id]);

        return redirect()->route('admin.posts.edit', $copy)->with('status', 'Copy created as a draft.');
    }

    public function destroy(Post $post)
    {
        $paths = $this->pathsOf($post);
        AuditLog::record('post.deleted', $post, null, ['title' => $post->title, 'slug' => $post->slug]);
        $post->delete();
        foreach ($paths as $p) {
            $this->discard($p, null);
        }

        return redirect()->route('admin.posts')->with('status', 'Post deleted.');
    }

    /** One image from the editor: compressed, then parked until the post is saved. */
    public function uploadImage(Request $request)
    {
        $max = config('marketplace.max_upload_kb.image');
        $request->validate(['image' => "required|image|mimes:jpg,jpeg,png,webp,gif|max:{$max}|dimensions:max_width=8000,max_height=8000"]);
        $stored = Media::storeWithInfo($request->file('image'), 'posts');
        MediaUpload::create(['user_id' => $request->user()->id] + $stored);

        return response()->json(['path' => $stored['path'], 'url' => Media::url($stored['path']), 'bytes' => $stored['bytes'], 'original_bytes' => $stored['original_bytes']], 201);
    }

    /** A downloadable file (a study as PDF, a data sheet…). Stored as-is. */
    public function uploadFile(Request $request)
    {
        $request->validate(['file' => 'required|file|max:25600|mimes:pdf,doc,docx,xls,xlsx,csv,ppt,pptx,zip,txt']);
        $file = $request->file('file');
        $name = Str::limit(Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME)), 60, '') ?: 'file';
        $key = 'posts/files/'.Str::lower(Str::random(8)).'-'.$name.'.'.strtolower($file->getClientOriginalExtension() ?: $file->extension());
        Storage::disk(Media::disk())->putFileAs(dirname($key), $file, basename($key));
        $bytes = (int) $file->getSize();
        MediaUpload::create(['user_id' => $request->user()->id, 'path' => $key, 'bytes' => $bytes, 'original_bytes' => $bytes]);

        return response()->json(['path' => $key, 'url' => Media::url($key), 'bytes' => $bytes, 'name' => $file->getClientOriginalName()], 201);
    }

    // ───────────────────────────────────────────────────────────────

    private function applyIntent(Post $post, array $data): string
    {
        $intent = $data['intent'];
        $when = $data['publish_at'];
        if ($intent === 'publish') {
            $post->forceFill(['status' => 'published', 'published_at' => $when ?? ($post->published_at && $post->status === 'published' ? $post->published_at : now())])->save();
            $scheduled = $post->isScheduled();
            AuditLog::record($scheduled ? 'post.scheduled' : 'post.published', $post, null, ['title' => $post->title]);

            return $scheduled ? 'Scheduled — it goes live '.$post->published_at->format('M j \a\t g:ia').'.' : 'Published — it is live on Insights.';
        }
        if ($intent === 'draft' && $post->status !== 'draft') {
            $post->forceFill(['status' => 'draft'])->save();

            return 'Saved as a draft. It is no longer public.';
        }
        if ($when && $post->status === 'published') {
            $post->forceFill(['published_at' => $when])->save();
        }

        return $post->status === 'published' ? 'Changes saved and live.' : 'Draft saved.';
    }

    private function validated(Request $request, ?Post $post): array
    {
        $pending = MediaUpload::pendingPaths($request->user()->id);
        $allowed = array_merge($post ? $this->pathsOf($post) : [], $pending);
        $v = $request->validate([
            'intent' => 'required|in:draft,save,publish',
            'title' => 'required|string|max:180',
            'excerpt' => 'nullable|string|max:400',
            'type' => ['required', Rule::in(array_keys(Post::TYPES))],
            'audience' => ['required', Rule::in(array_keys(Post::AUDIENCES))],
            'topics' => 'nullable|string|max:300',
            'author_label' => 'nullable|string|max:120',
            'slug' => ['nullable', 'string', 'max:120', 'regex:/^[a-z0-9-]+$/'],
            'seo_title' => 'nullable|string|max:70',
            'seo_description' => 'nullable|string|max:180',
            'publish_at' => 'nullable|date',
            'featured' => 'nullable|boolean',
            'settings' => 'nullable|array',
            'cover_upload' => ['nullable', 'string', Rule::in($pending)],
            'remove_cover' => 'nullable|boolean',
            'cover_alt' => 'nullable|string|max:200',
            'cover_caption' => 'nullable|string|max:200',
            'blocks' => 'nullable|array|max:80',
            'blocks.*.type' => ['required', Rule::in(PostBlock::TYPES)],
            'blocks.*.settings' => 'nullable|string|max:1000',
            'blocks.*.title' => 'nullable|string|max:300',
            'blocks.*.body' => 'nullable|string|max:20000',
            'blocks.*.caption' => 'nullable|string|max:300',
            'blocks.*.url' => 'nullable|string|max:500',
            'blocks.*.alt' => 'nullable|string|max:200',
            'blocks.*.size' => 'nullable|string|max:20',
            'blocks.*.existing' => ['nullable', 'string', Rule::in($allowed)],
            'blocks.*.gallery' => 'nullable|array|max:12',
            'blocks.*.gallery.*.path' => ['required', 'string', Rule::in($allowed)],
            'blocks.*.gallery.*.alt' => 'nullable|string|max:200',
            'blocks.*.items' => 'nullable|array|max:16',
            'blocks.*.items.*.a' => 'nullable|string|max:200',
            'blocks.*.items.*.b' => 'nullable|string|max:2000',
        ], [
            'slug.regex' => 'The web address can only use lowercase letters, numbers and dashes.',
            'blocks.*.existing.in' => 'An image or file in the post is no longer available. Upload it again.',
        ]);

        foreach ($v['blocks'] ?? [] as $b) {
            if ($b['type'] === 'embed' && filled($b['url'] ?? null) && ! preg_match('~^https://(www\.)?(youtube\.com|youtu\.be|vimeo\.com)/~', $b['url'])) {
                throw ValidationException::withMessages(['blocks' => 'Video blocks only support YouTube and Vimeo links.']);
            }
            if ($b['type'] === 'button' && filled($b['url'] ?? null) && ! PostBlock::safeUrl($b['url'])) {
                throw ValidationException::withMessages(['blocks' => 'Button links must start with https://, mailto: or / (a page on this site).']);
            }
        }

        $settings = Post::cleanSettings($v['settings'] ?? []);
        $publishAt = filled($v['publish_at'] ?? null) ? Carbon::parse($v['publish_at']) : null;

        return [
            'intent' => $v['intent'],
            'publish_at' => $publishAt,
            'slug' => filled($v['slug'] ?? null) ? Str::slug($v['slug']) : null,
            'cover_upload' => $v['cover_upload'] ?? null,
            'remove_cover' => (bool) ($v['remove_cover'] ?? false),
            'blocks' => $v['blocks'] ?? [],
            'attributes' => [
                'title' => $v['title'],
                'excerpt' => $v['excerpt'] ?? null,
                'type' => $v['type'],
                'audience' => $v['audience'],
                'topics' => collect(explode(',', (string) ($v['topics'] ?? '')))->map(fn ($t) => trim($t))->filter()->unique()->take(8)->values()->all(),
                'author_label' => $v['author_label'] ?? null,
                'seo_title' => $v['seo_title'] ?? null,
                'seo_description' => $v['seo_description'] ?? null,
                'settings' => $settings,
                'cover_alt' => $v['cover_alt'] ?? null,
                'cover_caption' => $v['cover_caption'] ?? null,
                'featured_at' => ($v['featured'] ?? false) ? ($post?->featured_at ?? now()) : null,
            ],
        ];
    }

    private function syncBlocks(Post $post, array $blocks, Request $request): void
    {
        $before = $this->pathsOf($post);
        $post->blocks()->delete();
        $i = 0;
        foreach ($blocks as $b) {
            $type = $b['type'];
            $body = $b['body'] ?? null;
            $title = $b['title'] ?? null;
            $path = in_array($type, ['image', 'image_text', 'download'], true) ? ($b['existing'] ?? null) : null;
            $gallery = $type === 'gallery' ? collect($b['gallery'] ?? [])->map(fn ($g) => ['path' => $g['path'], 'alt' => $g['alt'] ?? null])->values()->all() : [];
            $items = in_array($type, ['stats', 'faq'], true)
                ? collect($b['items'] ?? [])->map(fn ($it) => ['a' => trim((string) ($it['a'] ?? '')), 'b' => trim((string) ($it['b'] ?? ''))])->filter(fn ($it) => $it['a'] !== '' || $it['b'] !== '')->values()->all()
                : ($type === 'download' && filled($b['size'] ?? null) ? ['size' => $b['size']] : null);
            $empty = match ($type) {
                'heading', 'text', 'quote', 'list', 'table' => blank($body),
                'image' => ! $path,
                'image_text' => ! $path && blank($body),
                'gallery' => ! $gallery,
                'embed' => blank($b['url'] ?? null),
                'callout' => blank($body) && blank($title),
                'stats', 'faq' => ! $items,
                'button' => blank($b['caption'] ?? null) && blank($title),
                'download' => ! $path,
                default => false,
            };
            if ($empty) {
                continue;
            }
            $settings = json_decode((string) ($b['settings'] ?? ''), true);
            $post->blocks()->create([
                'type' => $type,
                'title' => in_array($type, ['image_text', 'callout', 'list', 'stats', 'table', 'faq', 'button', 'download'], true) ? $title : null,
                'body' => $type === 'spacer' ? null : $body,
                'caption' => $b['caption'] ?? null,
                'url' => in_array($type, ['embed', 'button'], true) ? ($b['url'] ?? null) : null,
                'alt' => in_array($type, ['image', 'image_text'], true) ? ($b['alt'] ?? null) : null,
                'media_path' => $path,
                'media' => $gallery ?: null,
                'items' => $items ?: null,
                'settings' => PostBlock::cleanSettings($type, is_array($settings) ? $settings : []),
                'sort_order' => $i++,
            ]);
        }
        $post->load('blocks');
        $after = $this->pathsOf($post);
        MediaUpload::claim($request->user()->id, $after);
        foreach (array_diff($before, $after) as $p) {
            $this->discard($p, $post);
        }
        $post->forceFill(['reading_minutes' => $post->computeReadingMinutes()])->saveQuietly();
    }

    /** Every stored file a post uses (cover, images, gallery, downloads). */
    private function pathsOf(Post $post): array
    {
        return collect([$post->cover_path])
            ->merge($post->blocks()->get()->flatMap(fn ($b) => array_merge([$b->media_path], array_column($b->media ?? [], 'path'))))
            ->filter()->unique()->values()->all();
    }

    /** Deletes a stored file unless another post (e.g. a duplicate) still uses it. */
    private function discard(?string $path, ?Post $except): void
    {
        if (! $path) {
            return;
        }
        $used = Post::where('cover_path', $path)->when($except, fn ($q) => $q->whereKeyNot($except->id))->exists()
            || PostBlock::where(fn ($q) => $q->where('media_path', $path)->orWhere('media', 'like', '%'.str_replace('/', '\\\\/', $path).'%')->orWhere('media', 'like', '%'.$path.'%'))
                ->when($except, fn ($q) => $q->where('post_id', '!=', $except->id))->exists()
            // Never delete a file a portfolio still shows (e.g. a spotlight reusing a creator's image).
            || DB::table('portfolio_projects')->where('cover_path', $path)->exists()
            || DB::table('portfolio_blocks')->where('media_path', $path)->orWhere('media', 'like', '%'.$path.'%')->orWhere('media', 'like', '%'.str_replace('/', '\\/', $path).'%')->exists();
        if (! $used) {
            Media::delete($path);
        }
    }
}
