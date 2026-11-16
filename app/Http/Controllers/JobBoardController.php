<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\JobPost;
use App\Support\Money;
use Illuminate\Http\Request;

class JobBoardController extends Controller
{
    public function index(Request $request)
    {
        $f = $request->validate([
            'q' => 'nullable|string|max:100',
            'location' => 'nullable|string|max:100',
            'category' => 'nullable|string|max:80',
            'level' => 'nullable|array', 'level.*' => 'in:entry,intermediate,expert',
            'type' => 'nullable|in:hourly,fixed',
            'budget' => 'nullable|array', 'budget.*' => 'in:lt5k,5k-20k,20k-50k,50k-100k,gt100k',
            'proposals' => 'nullable|array', 'proposals.*' => 'in:lt5,5-10,10-15,15-20,gt20',
            'sort' => 'nullable|in:newest,oldest,budget_high,budget_low',
            'view' => 'nullable|in:grid,list',
        ]);

        $base = JobPost::live()
            ->when($f['q'] ?? null, fn ($q, $term) => $q->where(fn ($w) => $w->where('title', 'like', "%{$term}%")->orWhere('description', 'like', "%{$term}%")->orWhereHas('skills', fn ($s) => $s->where('name', 'like', "%{$term}%"))))
            ->when($f['location'] ?? null, fn ($q, $loc) => $q->where('location', 'like', "%{$loc}%"))
            ->when($f['category'] ?? null, fn ($q, $c) => $q->whereHas('category', fn ($w) => $w->where('slug', $c)))
            ->when($f['type'] ?? null, fn ($q, $t) => $q->where('budget_type', $t));

        // Facet counts reflect search but not the facet itself.
        $levelCounts = (clone $base)->selectRaw('experience_level, count(*) c')->groupBy('experience_level')->pluck('c', 'experience_level');
        $budgetRanges = [
            'lt5k' => ['Less than 5K', 0, 5000], '5k-20k' => ['5K to 20K', 5000, 20000], '20k-50k' => ['20K to 50K', 20000, 50000],
            '50k-100k' => ['50K to 100K', 50000, 100000], 'gt100k' => ['100K+', 100000, null],
        ];
        $budgetCounts = collect($budgetRanges)->map(fn ($r) => (clone $base)->where('budget_max_minor', '>=', $r[1] * 100)->when($r[2], fn ($q) => $q->where('budget_max_minor', '<', $r[2] * 100))->count());
        $proposalRanges = ['lt5' => ['Less than 5', 0, 4], '5-10' => ['5 to 10', 5, 10], '10-15' => ['10 to 15', 11, 15], '15-20' => ['15 to 20', 16, 20], 'gt20' => ['20+', 21, null]];

        $query = (clone $base)
            ->when($f['level'] ?? null, fn ($q, $l) => $q->whereIn('experience_level', $l))
            ->when($f['budget'] ?? null, function ($q, $sel) use ($budgetRanges) {
                $q->where(function ($w) use ($sel, $budgetRanges) {
                    foreach ($sel as $k) {
                        [, $min, $max] = $budgetRanges[$k];
                        $w->orWhere(fn ($x) => $x->where('budget_max_minor', '>=', $min * 100)->when($max, fn ($y) => $y->where('budget_max_minor', '<', $max * 100)));
                    }
                });
            })
            ->when($f['proposals'] ?? null, function ($q, $sel) use ($proposalRanges) {
                $q->where(function ($w) use ($sel, $proposalRanges) {
                    foreach ($sel as $k) {
                        [, $min, $max] = $proposalRanges[$k];
                        $w->orWhere(fn ($x) => $x->has('applications', '>=', $min)->when($max !== null, fn ($y) => $y->has('applications', '<=', $max)));
                    }
                });
            })
            ->with(['client.user', 'category', 'skills'])->withCount('applications');

        $query = match ($f['sort'] ?? 'newest') {
            'oldest' => $query->oldest('published_at'),
            'budget_high' => $query->orderByDesc('budget_max_minor'),
            'budget_low' => $query->orderBy('budget_max_minor'),
            default => $query->orderByDesc('is_featured')->latest('published_at'),
        };

        $jobs = $query->paginate(10)->withQueryString();
        $categories = Category::orderBy('sort_order')->get();
        $savedIds = $request->user()?->savedJobs()->pluck('job_posts.id')->all() ?? [];
        $appliedIds = $request->user()?->freelancerProfile?->applications()->pluck('job_post_id')->all() ?? [];

        return view('jobs.index', compact('jobs', 'categories', 'levelCounts', 'budgetRanges', 'budgetCounts', 'proposalRanges', 'savedIds', 'appliedIds', 'f'));
    }

    public function show(Request $request, JobPost $job)
    {
        $user = $request->user();
        abort_unless($job->isLive() || $job->ownedBy($user) || $user?->isAdmin(), 404);
        $job->load(['client.user', 'category', 'skills', 'questions'])->loadCount('applications');
        $application = $user?->freelancerProfile ? $job->applications()->where('freelancer_profile_id', $user->freelancerProfile->id)->first() : null;
        $saved = $user ? $user->savedJobs()->whereKey($job->id)->exists() : false;
        $similar = JobPost::live()->where('id', '!=', $job->id)->where('category_id', $job->category_id)->with('client.user')->take(3)->get();
        $clientStats = [
            'jobs' => $job->client->jobPosts()->whereNotIn('status', ['draft', 'pending_payment'])->count(),
            'since' => $job->client->user->created_at,
        ];

        return view('jobs.show', compact('job', 'application', 'saved', 'similar', 'clientStats'));
    }

    public function toggleSave(Request $request, JobPost $job)
    {
        abort_unless($job->isLive(), 404);
        $request->user()->savedJobs()->toggle([$job->id]);

        return back();
    }

    public function saved(Request $request)
    {
        $jobs = $request->user()->savedJobs()->with(['client.user', 'category', 'skills'])->withCount('applications')->latest('saved_jobs.created_at')->paginate(10);

        return view('jobs.saved', compact('jobs'));
    }
}
