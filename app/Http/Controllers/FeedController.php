<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\PortfolioProject;
use App\Models\Review;
use Illuminate\Http\Request;

/** Signed-in home: a Behance-style discovery feed of published portfolio projects. */
class FeedController extends Controller
{
    public function index(Request $request)
    {
        $f = $request->validate([
            'q' => 'nullable|string|max:100',
            'category' => 'nullable|string|max:80',
            'sort' => 'nullable|in:recommended,newest,most_viewed,most_liked',
        ]);
        $sort = $f['sort'] ?? 'recommended';
        $user = $request->user();

        $base = fn () => PortfolioProject::discoverable()
            ->with(['profile.user', 'category'])
            ->withCount('likes');

        // Bento "featured" block: top recent work by views + likes, only for the unfiltered feed and
        // only when there's enough of it. Those projects are then excluded from the grid on every page.
        $unfiltered = empty($f['q']) && empty($f['category']) && $sort === 'recommended';
        $candidates = $unfiltered
            ? $base()->where('published_at', '>=', now()->subDays(120))
                ->orderByRaw('views + 20 * (select count(*) from portfolio_likes pl where pl.portfolio_project_id = portfolio_projects.id) desc')
                ->take(5)->get()
            : collect();
        $useBento = $candidates->count() >= 3;
        $bentoIds = $useBento ? $candidates->pluck('id') : collect();
        $bento = $useBento && (int) $request->query('page', 1) === 1 ? $candidates : collect();

        $query = $base()
            ->when($bentoIds->isNotEmpty(), fn ($q) => $q->whereNotIn('id', $bentoIds))
            ->when($f['q'] ?? null, fn ($q, $t) => $q->where(fn ($w) => $w->where('title', 'like', "%{$t}%")
                ->orWhere('summary', 'like', "%{$t}%")
                ->orWhere('tags', 'like', "%{$t}%")
                ->orWhereHas('profile.user', fn ($u) => $u->where('name', 'like', "%{$t}%"))))
            ->when($f['category'] ?? null, fn ($q, $c) => $q->whereHas('category', fn ($w) => $w->where('slug', $c)));

        $query = match ($sort) {
            'newest' => $query->latest('published_at'),
            'most_viewed' => $query->orderByDesc('views'),
            'most_liked' => $query->orderByDesc('likes_count')->orderByDesc('views'),
            // Recommended: the viewer's own category first (freelancers), then a recency/popularity mix.
            default => $query
                ->when($user?->freelancerProfile?->category_id, fn ($q, $cat) => $q->orderByRaw('CASE WHEN category_id = ? THEN 0 ELSE 1 END', [$cat]))
                ->latest('published_at'),
        };

        $projects = $query->paginate(24)->withQueryString();
        $all = $bento->concat($projects->getCollection());

        $ratings = Review::whereIn('reviewee_id', $all->pluck('profile.user_id')->unique())
            ->selectRaw('reviewee_id, round(avg(rating), 1) as avg_rating')->groupBy('reviewee_id')->pluck('avg_rating', 'reviewee_id');
        $likedIds = $user ? $user->belongsToMany(PortfolioProject::class, 'portfolio_likes')->whereIn('portfolio_projects.id', $all->pluck('id'))->pluck('portfolio_projects.id')->all() : [];

        return view('feed.index', [
            'bento' => $bento,
            'projects' => $projects,
            'categories' => Category::orderBy('sort_order')->get(),
            'ratings' => $ratings,
            'likedIds' => $likedIds,
            'f' => $f,
            'sort' => $sort,
        ]);
    }

    public function toggleLike(Request $request, PortfolioProject $project)
    {
        abort_unless(PortfolioProject::discoverable()->whereKey($project->id)->exists(), 404);
        abort_if($project->profile->user_id === $request->user()->id, 422, 'You cannot like your own project.');

        $result = $project->likes()->toggle([$request->user()->id]);
        $liked = ! empty($result['attached']);
        $count = $project->likes()->count();

        return $request->expectsJson() ? response()->json(['liked' => $liked, 'count' => $count]) : back();
    }
}
