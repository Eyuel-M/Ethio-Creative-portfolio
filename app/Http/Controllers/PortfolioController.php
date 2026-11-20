<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\PortfolioBlock;
use App\Models\PortfolioProject;
use App\Support\Media;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class PortfolioController extends Controller
{
    public function index(Request $request)
    {
        $profile = $request->user()->freelancerProfile;
        $projects = $profile->portfolioProjects()->withCount('blocks')->get();

        return view('portfolio.index', ['projects' => $projects, 'profile' => $profile, 'limit' => $profile->portfolioLimit()]);
    }

    public function show(Request $request, PortfolioProject $project)
    {
        $project->load(['profile.user', 'profile.skills', 'blocks', 'category']);
        $isOwner = $request->user()?->id === $project->profile->user_id;
        abort_unless(($project->isPublished() && $project->profile->is_public && $project->profile->user->isActive()) || $isOwner || $request->user()?->isAdmin(), 404);
        if (! $isOwner) {
            $project->increment('views');
        }
        $more = $project->profile->publishedProjects()->where('id', '!=', $project->id)->with('category')->withCount('likes')->take(8)->get();
        $likedIds = $request->user() ? \Illuminate\Support\Facades\DB::table('portfolio_likes')->where('user_id', $request->user()->id)->whereIn('portfolio_project_id', $more->pluck('id')->push($project->id))->pluck('portfolio_project_id')->all() : [];

        return view('portfolio.show', compact('project', 'more', 'isOwner', 'likedIds'));
    }

    public function create(Request $request)
    {
        $profile = $request->user()->freelancerProfile;
        if ($profile->portfolioProjects()->count() >= $profile->portfolioLimit()) {
            return redirect()->route('portfolio.index')->with('status', 'You have reached your portfolio limit. Upgrade for more project panels.');
        }

        return view('portfolio.form', ['project' => new PortfolioProject, 'categories' => Category::orderBy('sort_order')->get()]);
    }

    public function store(Request $request)
    {
        $profile = $request->user()->freelancerProfile;
        if ($profile->portfolioProjects()->count() >= $profile->portfolioLimit()) {
            throw ValidationException::withMessages(['title' => 'Portfolio limit reached for your plan.']);
        }
        $data = $this->validated($request, null);

        $project = DB::transaction(function () use ($profile, $data, $request) {
            $project = new PortfolioProject($data['attributes']);
            $project->freelancer_profile_id = $profile->id;
            $project->status = 'draft';
            if ($request->hasFile('cover')) {
                $project->cover_path = Media::store($request->file('cover'), 'portfolio');
            }
            $project->save();
            $this->syncBlocks($project, $data['blocks'], $request);

            return $project;
        });

        if ($request->boolean('publish')) {
            $this->publish($project);
        }

        return redirect()->route('portfolio.edit', $project)->with('status', $project->isPublished() ? 'Project published.' : 'Draft saved.');
    }

    public function edit(Request $request, PortfolioProject $project)
    {
        $this->authorizeOwner($request, $project);
        $project->load('blocks');

        return view('portfolio.form', ['project' => $project, 'categories' => Category::orderBy('sort_order')->get()]);
    }

    public function update(Request $request, PortfolioProject $project)
    {
        $this->authorizeOwner($request, $project);
        $data = $this->validated($request, $project);

        DB::transaction(function () use ($project, $data, $request) {
            $project->fill($data['attributes']);
            if ($request->hasFile('cover')) {
                Media::delete($project->cover_path);
                $project->cover_path = Media::store($request->file('cover'), 'portfolio');
            }
            $project->save();
            $this->syncBlocks($project, $data['blocks'], $request);
        });

        if ($request->boolean('publish') && ! $project->isPublished()) {
            $this->publish($project);
        }

        return back()->with('status', 'Project saved.');
    }

    public function togglePublish(Request $request, PortfolioProject $project)
    {
        $this->authorizeOwner($request, $project);
        if ($project->isPublished()) {
            $project->forceFill(['status' => 'draft'])->save();

            return back()->with('status', 'Project unpublished — it is no longer public.');
        }
        $this->publish($project);

        return back()->with('status', 'Project published.');
    }

    public function destroy(Request $request, PortfolioProject $project)
    {
        $this->authorizeOwner($request, $project);
        Media::delete($project->cover_path);
        foreach ($project->blocks as $b) {
            Media::delete($b->media_path);
            foreach (array_column($b->media ?? [], 'path') as $p) {
                Media::delete($p);
            }
        }
        $project->delete();

        return redirect()->route('portfolio.index')->with('status', 'Project deleted.');
    }

    private function publish(PortfolioProject $project): void
    {
        if (! $project->cover_path && ! $project->blocks()->where(fn ($q) => $q->whereNotNull('media_path')->orWhereNotNull('media'))->exists()) {
            throw ValidationException::withMessages(['cover' => 'Add a cover image or at least one image block before publishing.']);
        }
        $project->forceFill(['status' => 'published', 'published_at' => $project->published_at ?? now()])->save();
    }

    private function authorizeOwner(Request $request, PortfolioProject $project): void
    {
        abort_unless($project->freelancer_profile_id === $request->user()->freelancerProfile?->id, 403);
    }

    private function validated(Request $request, ?PortfolioProject $project): array
    {
        $existing = $project ? $project->blocks()->get()->flatMap(fn ($b) => array_merge([$b->media_path], array_column($b->media ?? [], 'path')))->filter()->values()->all() : [];
        $max = config('marketplace.max_upload_kb.image');
        $v = $request->validate([
            'title' => 'required|string|max:140',
            'summary' => 'nullable|string|max:500',
            'category_id' => 'nullable|exists:categories,id',
            'tags' => 'nullable|string|max:300',
            'client_attribution' => 'nullable|string|max:120',
            'project_date' => 'nullable|date',
            'cover' => "nullable|image|mimes:jpg,jpeg,png,webp,gif|max:{$max}|dimensions:max_width=8000,max_height=8000",
            'cover_alt' => 'nullable|string|max:200',
            'blocks' => 'nullable|array|max:40',
            'blocks.*.type' => 'required|in:'.implode(',', PortfolioBlock::TYPES),
            'blocks.*.settings' => 'nullable|string|max:1000',
            'blocks.*.gallery' => 'nullable|array|max:12',
            'blocks.*.gallery.*.path' => ['required', 'string', Rule::in($existing)],
            'blocks.*.gallery.*.alt' => 'nullable|string|max:200',
            'blocks.*.images' => 'nullable|array|max:12',
            'blocks.*.image_alts' => 'nullable|array|max:12',
            'blocks.*.image_alts.*' => 'nullable|string|max:200',
            'blocks.*.images.*' => "image|mimes:jpg,jpeg,png,webp,gif|max:{$max}|dimensions:max_width=8000,max_height=8000",
            'blocks.*.body' => 'nullable|string|max:5000',
            'blocks.*.caption' => 'nullable|string|max:200',
            'blocks.*.alt' => 'nullable|string|max:200',
            'blocks.*.embed_url' => ['nullable', 'url', 'max:300', 'regex:~^https://(www\.)?(youtube\.com|youtu\.be|vimeo\.com)/~'],
            'blocks.*.image' => "nullable|image|mimes:jpg,jpeg,png,webp,gif|max:{$max}|dimensions:max_width=8000,max_height=8000",
            'blocks.*.existing' => ['nullable', 'string', Rule::in($existing)],
        ], ['blocks.*.embed_url.regex' => 'Only YouTube and Vimeo embeds are supported.']);

        return [
            'attributes' => [
                'title' => $v['title'],
                'summary' => $v['summary'] ?? null,
                'category_id' => $v['category_id'] ?? null,
                'tags' => collect(explode(',', (string) ($v['tags'] ?? '')))->map(fn ($t) => trim($t))->filter()->take(12)->values()->all(),
                'client_attribution' => $v['client_attribution'] ?? null,
                'project_date' => $v['project_date'] ?? null,
                'cover_alt' => $v['cover_alt'] ?? null,
            ],
            'blocks' => $v['blocks'] ?? [],
        ];
    }

    private function syncBlocks(PortfolioProject $project, array $blocks, Request $request): void
    {
        $old = $project->blocks()->get();
        $kept = [];
        $project->blocks()->delete();
        $i = 0;
        foreach ($blocks as $key => $b) {
            $type = $b['type'];
            $usesImage = in_array($type, ['image', 'image_text'], true);
            $path = $usesImage ? ($b['existing'] ?? null) : null;
            if ($usesImage && ($file = $request->file("blocks.{$key}.image"))) {
                $path = Media::store($file, 'portfolio');
            }
            $gallery = [];
            if ($type === 'gallery') {
                foreach ($b['gallery'] ?? [] as $g) {
                    $gallery[] = ['path' => $g['path'], 'alt' => $g['alt'] ?? null];
                }
                foreach ($request->file("blocks.{$key}.images") ?? [] as $idx => $file) {
                    if (count($gallery) < 12) {
                        $gallery[] = ['path' => Media::store($file, 'portfolio'), 'alt' => $b['image_alts'][$idx] ?? null];
                    }
                }
            }
            $body = $b['body'] ?? null;
            $empty = match ($type) {
                'heading', 'text', 'quote' => blank($body),
                'image' => ! $path,
                'image_text' => ! $path && blank($body),
                'gallery' => ! $gallery,
                'embed' => blank($b['embed_url'] ?? null),
                default => false,
            };
            if ($empty) {
                continue;
            }
            $kept = array_merge($kept, [$path], array_column($gallery, 'path'));
            $settings = json_decode((string) ($b['settings'] ?? ''), true);
            $project->blocks()->create([
                'type' => $type,
                'body' => in_array($type, ['heading', 'text', 'quote', 'image_text'], true) ? $body : null,
                'media_path' => $path,
                'media' => $gallery ?: null,
                'caption' => in_array($type, ['image', 'embed', 'quote', 'gallery'], true) ? ($b['caption'] ?? null) : null,
                'alt' => $usesImage ? ($b['alt'] ?? null) : null,
                'embed_url' => $type === 'embed' ? $b['embed_url'] : null,
                'settings' => PortfolioBlock::cleanSettings($type, is_array($settings) ? $settings : []),
                'sort_order' => $i++,
            ]);
        }
        foreach ($old as $o) {
            foreach (array_merge([$o->media_path], array_column($o->media ?? [], 'path')) as $p) {
                if ($p && $p !== $project->cover_path && ! in_array($p, $kept, true)) {
                    Media::delete($p);
                }
            }
        }
    }
}
