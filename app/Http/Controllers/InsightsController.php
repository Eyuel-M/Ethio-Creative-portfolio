<?php

namespace App\Http\Controllers;

use App\Models\Post;
use Illuminate\Http\Request;

/** Public Insights: studies, guides, product updates and stories from the team. */
class InsightsController extends Controller
{
    public function index(Request $request)
    {
        $f = $request->validate([
            'type' => 'nullable|in:'.implode(',', array_keys(Post::TYPES)),
            'for' => 'nullable|in:freelancers,clients',
            'topic' => 'nullable|string|max:60',
            'q' => 'nullable|string|max:100',
        ]);
        $filtered = (bool) array_filter($f);
        $base = fn () => Post::public()->with('author')
            ->when($f['type'] ?? null, fn ($q, $t) => $q->where('type', $t))
            // "For freelancers" also shows posts written for everyone.
            ->when($f['for'] ?? null, fn ($q, $a) => $q->whereIn('audience', [$a, 'everyone']))
            ->when($f['topic'] ?? null, fn ($q, $t) => $q->where('topics', 'like', '%"'.str_replace(['%', '_'], ['\%', '\_'], $t).'"%'))
            ->when($f['q'] ?? null, fn ($q, $t) => $q->where(fn ($w) => $w->where('title', 'like', "%{$t}%")->orWhere('excerpt', 'like', "%{$t}%")));

        // The lead story: a pinned post, else the newest — only on the unfiltered first page.
        $lead = ! $filtered && (int) $request->query('page', 1) === 1
            ? $base()->orderByRaw('CASE WHEN featured_at IS NULL THEN 1 ELSE 0 END')->orderByDesc('featured_at')->latest('published_at')->first()
            : null;
        $posts = $base()->when($lead, fn ($q) => $q->whereKeyNot($lead->id))
            ->orderByRaw('CASE WHEN featured_at IS NULL THEN 1 ELSE 0 END')->latest('published_at')
            ->paginate(12)->withQueryString();

        $all = Post::public()->get(['type', 'topics']);
        $typeCounts = $all->countBy('type');
        $topics = $all->pluck('topics')->flatten()->filter()->countBy()->sortDesc()->take(10)->keys();
        $studies = ! $filtered ? Post::public()->where('type', 'study')->when($lead, fn ($q) => $q->whereKeyNot($lead->id))->latest('published_at')->take(3)->get() : collect();

        return view('insights.index', compact('f', 'lead', 'posts', 'typeCounts', 'topics', 'studies', 'filtered'));
    }

    public function show(Request $request, Post $post)
    {
        $user = $request->user();
        $staff = $user && $user->isAdmin() && $user->canAdmin('posts');
        abort_unless($post->isPublic() || $staff, 404);

        $post->load(['blocks', 'author']);
        // One read per visitor session; staff previews aren't counted.
        if ($post->isPublic() && ! $staff && ! $request->session()->has("read.{$post->id}")) {
            $request->session()->put("read.{$post->id}", true);
            $post->increment('views');
            \App\Support\Analytics::track('post_view', $post, ['type' => $post->type]);
        }

        $topics = $post->topics ?? [];
        $related = $post->shows('related')
            ? Post::public()->whereKeyNot($post->id)->get()
                ->sortByDesc(fn ($p) => count(array_intersect($p->topics ?? [], $topics)) * 3 + ($p->type === $post->type ? 2 : 0) + $p->published_at->timestamp / 1e10)
                ->take(3)->values()
            : collect();

        return view('insights.show', compact('post', 'related', 'staff'));
    }

    public function feed()
    {
        $posts = Post::public()->with(['author', 'blocks'])->latest('published_at')->take(30)->get();

        return response()->view('insights.feed', compact('posts'))->header('Content-Type', 'application/rss+xml; charset=UTF-8');
    }
}
