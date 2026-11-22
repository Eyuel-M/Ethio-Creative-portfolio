<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\PortfolioProject;
use App\Models\Review;
use Illuminate\Http\Request;

/** Signed-in home: a Behance-style discovery feed of published portfolio projects. */
class FeedController extends Controller
{
    /** Projects a visitor sees on /explore before being asked to sign up (last rows are blurred). */
    public const PREVIEW = 24;

    public function index(Request $request)
    {
        $f = $request->validate([
            'q' => 'nullable|string|max:100',
            'category' => 'nullable|string|max:80',
            'sort' => 'nullable|in:recommended,newest,most_viewed,most_liked',
            'available' => 'nullable|boolean',
            'pro' => 'nullable|boolean',
            'min_likes' => 'nullable|integer|in:0,5,10',
            'collection' => 'nullable|in:saved,liked',
            'liked' => 'nullable|boolean', // older links: ?liked=1
        ]);
        $user = $request->user();
        // Visitors get a preview: categories only — no search, filters, sorting or further pages.
        if (! $user) {
            $f = ['category' => $f['category'] ?? null];
        }
        $sort = $f['sort'] ?? 'recommended';

        $base = fn () => PortfolioProject::discoverable()
            ->with(['profile.user', 'category'])
            ->withCount('likes');

        // Bento "featured" block: top recent work by views + likes, only for the unfiltered feed and
        // only when there's enough of it. Those projects are then excluded from the grid on every page.
        $filterCount = collect([$f['available'] ?? false, $f['pro'] ?? false, ($f['min_likes'] ?? 0) > 0])->filter()->count();
        // Collection: the viewer's private saves, or the projects they liked.
        $collection = $user ? ($f['collection'] ?? (($f['liked'] ?? false) ? 'liked' : null)) : null;
        $f['collection'] = $collection;
        unset($f['liked']);
        $pivot = ['saved' => 'portfolio_saves', 'liked' => 'portfolio_likes'][$collection] ?? null;
        $unfiltered = ! $collection && empty($f['q']) && empty($f['category']) && $sort === 'recommended' && $filterCount === 0;
        $candidates = $unfiltered
            ? $base()->where(fn ($q) => $q->where('published_at', '>=', now()->subDays(120))->orWhereNotNull('featured_at'))
                ->orderByRaw('CASE WHEN featured_at IS NULL THEN 1 ELSE 0 END')->orderByDesc('featured_at') // admin-featured work leads
                ->orderByRaw('views + 20 * (select count(*) from portfolio_likes pl where pl.portfolio_project_id = portfolio_projects.id) desc')
                ->take(5)->get()
            : collect();
        $useBento = $user && $candidates->count() >= 3;
        $bentoIds = $useBento ? $candidates->pluck('id') : collect();
        $bento = $useBento && (int) $request->query('page', 1) === 1 ? $candidates : collect();

        $query = $base()
            ->when($bentoIds->isNotEmpty(), fn ($q) => $q->whereNotIn('id', $bentoIds))
            ->when($f['q'] ?? null, fn ($q, $t) => $q->where(fn ($w) => $w->where('title', 'like', "%{$t}%")
                ->orWhere('summary', 'like', "%{$t}%")
                ->orWhere('tags', 'like', "%{$t}%")
                ->orWhereHas('profile.user', fn ($u) => $u->where('name', 'like', "%{$t}%"))))
            ->when($f['category'] ?? null, fn ($q, $c) => $q->whereHas('category', fn ($w) => $w->where('slug', $c)))
            ->when($f['available'] ?? false, fn ($q) => $q->whereHas('profile', fn ($p) => $p->where('availability', 'available')))
            ->when($f['pro'] ?? false, fn ($q) => $q->whereHas('profile', fn ($p) => $p->where('plan', 'professional')))
            ->when(($f['min_likes'] ?? 0) > 0, fn ($q) => $q->has('likes', '>=', (int) $f['min_likes']))
            ->when($pivot, fn ($q) => $q->whereIn('id', fn ($s) => $s->select('portfolio_project_id')->from($pivot)->where('user_id', $user->id)));

        $query = $pivot && $sort === 'recommended'
            // Most recently saved/liked first.
            ? $query->orderByRaw("(select c.created_at from {$pivot} c where c.portfolio_project_id = portfolio_projects.id and c.user_id = ?) desc", [$user->id])
            : match ($sort) {
            'newest' => $query->latest('published_at'),
            'most_viewed' => $query->orderByDesc('views'),
            'most_liked' => $query->orderByDesc('likes_count')->orderByDesc('views'),
            // Recommended: the viewer's own category first (freelancers), then a recency/popularity mix.
            default => $query
                ->when($user?->freelancerProfile?->category_id, fn ($q, $cat) => $q->orderByRaw('CASE WHEN category_id = ? THEN 0 ELSE 1 END', [$cat]))
                ->latest('published_at'),
        };

        $projects = $user
            ? $query->paginate(24)->withQueryString()
            : $query->paginate(self::PREVIEW, ['*'], 'page', 1);
        $all = $bento->concat($projects->getCollection());

        $ratings = Review::whereNull('hidden_at')->whereIn('reviewee_id', $all->pluck('profile.user_id')->unique())
            ->selectRaw('reviewee_id, round(avg(rating), 1) as avg_rating')->groupBy('reviewee_id')->pluck('avg_rating', 'reviewee_id');
        $likedIds = $user ? $user->belongsToMany(PortfolioProject::class, 'portfolio_likes')->whereIn('portfolio_projects.id', $all->pluck('id'))->pluck('portfolio_projects.id')->all() : [];

        // Up to three of the most viewed covers per category, plus how many projects it holds, for the category chips.
        $catRows = PortfolioProject::discoverable()->whereNotNull('category_id')
            ->orderByDesc('views')->limit(1000)->get(['id', 'category_id', 'cover_path']);
        $categoryCovers = $catRows->whereNotNull('cover_path')->groupBy('category_id')
            ->map(fn ($g) => $g->take(3)->map(fn ($p) => $p->coverUrl())->values());
        $categoryCounts = $catRows->countBy('category_id');

        if (filled($f['q'] ?? null) && ! $request->has('page')) {
            \App\Support\Analytics::track('search', null, ['area' => 'work', 'q' => mb_strtolower(trim($f['q'])), 'results' => $projects->total()]);
        }

        return view('feed.index', [
            'feedRoute' => $user ? 'home' : 'explore',
            'categoryCovers' => $categoryCovers,
            'categoryCounts' => $categoryCounts,
            'collectionCounts' => $user ? [
                'saved' => PortfolioProject::discoverable()->whereIn('id', fn ($s) => $s->select('portfolio_project_id')->from('portfolio_saves')->where('user_id', $user->id))->count(),
                'liked' => PortfolioProject::discoverable()->whereIn('id', fn ($s) => $s->select('portfolio_project_id')->from('portfolio_likes')->where('user_id', $user->id))->count(),
            ] : ['saved' => 0, 'liked' => 0],
            'filterCount' => $filterCount,
            'bento' => $bento,
            'projects' => $projects,
            'categories' => Category::orderBy('sort_order')->get(),
            'ratings' => $ratings,
            'likedIds' => $likedIds,
            'f' => $f,
            'sort' => $sort,
        ]);
    }

    /** Public gallery for visitors; signed-in users get the same feed at "/". */
    public function explore(Request $request)
    {
        if ($request->user()) {
            return redirect()->route('home', $request->query());
        }

        return $this->index($request);
    }

    public function toggleLike(Request $request, PortfolioProject $project)
    {
        abort_unless(PortfolioProject::discoverable()->whereKey($project->id)->exists(), 404);
        abort_if($project->profile->user_id === $request->user()->id, 422, 'You cannot like your own project.');

        $result = $project->likes()->toggle([$request->user()->id]);
        $liked = ! empty($result['attached']);
        \App\Support\Analytics::track($liked ? 'like' : 'unlike', $project);
        $count = $project->likes()->count();

        return $request->expectsJson() ? response()->json(['liked' => $liked, 'count' => $count]) : back();
    }

    /** Private save (bookmark). Unlike a like, it isn't counted or shown to anyone else. */
    public function toggleSave(Request $request, PortfolioProject $project)
    {
        abort_unless(PortfolioProject::discoverable()->whereKey($project->id)->exists(), 404);
        abort_if($project->profile->user_id === $request->user()->id, 422, 'You cannot save your own project.');
        abort_if($request->user()->inAdminMode(), 403);

        $result = $project->savers()->toggle([$request->user()->id]);
        $saved = ! empty($result['attached']);
        \App\Support\Analytics::track($saved ? 'save' : 'unsave', $project);

        return $request->expectsJson() ? response()->json(['saved' => $saved]) : back();
    }
}
