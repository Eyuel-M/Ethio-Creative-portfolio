<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\FreelancerProfile;
use App\Models\Review;
use Illuminate\Http\Request;

class FreelancerDirectoryController extends Controller
{
    public function index(Request $request)
    {
        $f = $request->validate([
            'q' => 'nullable|string|max:100',
            'category' => 'nullable|string|max:80',
            'level' => 'nullable|array', 'level.*' => 'in:entry,intermediate,expert',
            'availability' => 'nullable|in:available,busy',
            'location' => 'nullable|string|max:100',
            'rating' => 'nullable|in:4,4.5',
            'rate_min' => 'nullable|numeric|min:0|max:1000000',
            'rate_max' => 'nullable|numeric|min:0|max:1000000',
            'languages' => 'nullable|array', 'languages.*' => 'string|max:40',
            'projects' => 'nullable|in:1,3,5',
            'skills' => 'nullable|array', 'skills.*' => 'string|max:80',
            'experienced' => 'nullable|boolean',
            'sort' => 'nullable|in:featured,newest,rating,rate_low,rate_high,projects',
        ]);
        $avgRating = '(select avg(r.rating) from reviews r where r.reviewee_id = freelancer_profiles.user_id)';

        $query = FreelancerProfile::public()
            ->when($f['q'] ?? null, fn ($q, $t) => $q->where(fn ($w) => $w->where('title', 'like', "%{$t}%")->orWhere('bio', 'like', "%{$t}%")
                ->orWhereHas('user', fn ($u) => $u->where('name', 'like', "%{$t}%"))
                ->orWhereHas('skills', fn ($s) => $s->where('name', 'like', "%{$t}%"))))
            ->when($f['category'] ?? null, fn ($q, $c) => $q->whereHas('category', fn ($w) => $w->where('slug', $c)))
            ->when($f['level'] ?? null, fn ($q, $l) => $q->whereIn('experience_level', $l))
            ->when($f['availability'] ?? null, fn ($q, $a) => $q->where('availability', $a))
            ->when($f['location'] ?? null, fn ($q, $l) => $q->where('location', 'like', "%{$l}%"))
            // Validated to exactly 4 or 4.5; inlined as a number because SQLite binds parameters as text,
            // and a computed average compared with text never matches.
            ->when($f['rating'] ?? null, fn ($q, $r) => $q->whereRaw($avgRating.' >= '.number_format((float) $r, 1, '.', '')))
            ->when($f['rate_min'] ?? null, fn ($q, $v) => $q->where('hourly_rate_minor', '>=', \App\Support\Money::toMinor($v)))
            ->when($f['rate_max'] ?? null, fn ($q, $v) => $q->whereNotNull('hourly_rate_minor')->where('hourly_rate_minor', '<=', \App\Support\Money::toMinor($v)))
            ->when($f['languages'] ?? null, fn ($q, $langs) => $q->where(function ($w) use ($langs) {
                foreach ($langs as $lang) {
                    $w->where('languages', 'like', '%"'.$lang.'"%');
                }
            }))
            ->when($f['projects'] ?? null, fn ($q, $n) => $q->has('publishedProjects', '>=', (int) $n))
            ->when($f['skills'] ?? null, fn ($q, $skills) => $q->whereHas('skills', fn ($w) => $w->whereIn('slug', $skills)))
            ->when($f['experienced'] ?? false, fn ($q) => $q->whereExists(fn ($e) => $e->selectRaw('1')->from('projects')->whereColumn('projects.freelancer_user_id', 'freelancer_profiles.user_id')->where('projects.status', 'completed')))
            ->with(['user', 'category', 'skills', 'publishedProjects' => fn ($q) => $q->take(8)])
            ->withCount('publishedProjects');

        // Featured placement is labelled as sponsored and never presented as earned reputation.
        $query = match ($f['sort'] ?? 'featured') {
            'newest' => $query->latest(),
            'rating' => $query->orderByRaw("coalesce({$avgRating}, 0) desc"),
            'rate_low' => $query->orderByRaw('hourly_rate_minor is null')->orderBy('hourly_rate_minor'),
            'rate_high' => $query->orderByDesc('hourly_rate_minor'),
            'projects' => $query->orderByDesc('published_projects_count'),
            default => $query->orderByRaw('CASE WHEN featured_until > ? THEN 0 ELSE 1 END', [now()])->latest('updated_at'),
        };

        $profiles = $query->paginate(12)->withQueryString();
        $categories = Category::orderBy('sort_order')->get();
        $shortlisted = $request->user()?->shortlist()->pluck('freelancer_profiles.id')->all() ?? [];

        // Filter option lists come from real data so every option can return results.
        $public = FreelancerProfile::public();
        $locations = (clone $public)->whereNotNull('location')->pluck('location')
            ->map(fn ($l) => trim(explode(',', $l)[0]))->filter()->countBy()->sortKeys();
        $languages = (clone $public)->whereNotNull('languages')->pluck('languages')->flatten()->filter()->countBy()->sortDesc()->take(8);
        $popularSkills = \App\Models\Skill::whereHas('freelancerProfiles', fn ($q) => $q->public())
            ->withCount(['freelancerProfiles' => fn ($q) => $q->public()])->orderByDesc('freelancer_profiles_count')->take(12)->get();
        $filterCount = collect(['category', 'level', 'availability', 'location', 'rating', 'rate_min', 'rate_max', 'languages', 'projects', 'skills', 'experienced'])
            ->filter(fn ($k) => filled($f[$k] ?? null))->count();

        if (filled($f['q'] ?? null) && ! $request->has('page')) {
            \App\Support\Analytics::track('search', null, ['area' => 'talent', 'q' => mb_strtolower(trim($f['q'])), 'results' => $profiles->total()]);
        } elseif ($filterCount && ! $request->has('page')) {
            \App\Support\Analytics::track('filter_used', null, ['area' => 'talent', 'filters' => $filterCount]);
        }

        return view('freelancers.index', compact('profiles', 'categories', 'f', 'shortlisted', 'locations', 'languages', 'popularSkills', 'filterCount'));
    }

    public function show(Request $request, FreelancerProfile $profile)
    {
        $viewer = $request->user();
        $isOwner = $viewer?->id === $profile->user_id;
        abort_unless(($profile->is_public && $profile->user->isActive()) || $isOwner || $viewer?->isAdmin(), 404);

        if (! $isOwner) {
            $profile->increment('profile_views');
            \App\Support\Analytics::track('profile_view', $profile, ['category' => $profile->category_id]);
        }

        $profile->load(['user', 'category', 'skills']);
        $projects = $profile->publishedProjects()->with('category')->get();
        $reviews = Review::where('reviewee_id', $profile->user_id)->whereNull('hidden_at')->with(['reviewer', 'project'])->latest()->take(10)->get();
        $rating = $profile->rating();
        $completed = \App\Models\Project::where('freelancer_user_id', $profile->user_id)->where('status', 'completed')->count();
        $clientJobs = $viewer?->clientProfile ? $viewer->clientProfile->jobPosts()->whereIn('status', ['published'])->get() : collect();
        $isShortlisted = $viewer ? $viewer->shortlist()->whereKey($profile->id)->exists() : false;

        return view('freelancers.show', compact('profile', 'projects', 'reviews', 'rating', 'completed', 'clientJobs', 'isShortlisted', 'isOwner'));
    }

    public function toggleShortlist(Request $request, FreelancerProfile $profile)
    {
        abort_unless($profile->is_public, 404);
        $res = $request->user()->shortlist()->toggle([$profile->id]);
        if (! empty($res['attached'])) {
            \App\Support\Analytics::track('saved_talent', $profile);
        }

        return back()->with('status', 'Shortlist updated.');
    }

    public function saved(Request $request)
    {
        $profiles = $request->user()->shortlist()->with(['user', 'category', 'skills', 'publishedProjects' => fn ($q) => $q->take(8)])->withCount('publishedProjects')->paginate(12);

        return view('freelancers.saved', compact('profiles'));
    }
}
