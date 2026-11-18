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
            'sort' => 'nullable|in:featured,newest,rating',
        ]);

        $query = FreelancerProfile::public()
            ->when($f['q'] ?? null, fn ($q, $t) => $q->where(fn ($w) => $w->where('title', 'like', "%{$t}%")->orWhere('bio', 'like', "%{$t}%")
                ->orWhereHas('user', fn ($u) => $u->where('name', 'like', "%{$t}%"))
                ->orWhereHas('skills', fn ($s) => $s->where('name', 'like', "%{$t}%"))))
            ->when($f['category'] ?? null, fn ($q, $c) => $q->whereHas('category', fn ($w) => $w->where('slug', $c)))
            ->when($f['level'] ?? null, fn ($q, $l) => $q->whereIn('experience_level', $l))
            ->when($f['availability'] ?? null, fn ($q, $a) => $q->where('availability', $a))
            ->when($f['location'] ?? null, fn ($q, $l) => $q->where('location', 'like', "%{$l}%"))
            ->with(['user', 'category', 'skills', 'publishedProjects' => fn ($q) => $q->take(8)])
            ->withCount('publishedProjects');

        // Featured placement is labelled as sponsored and never presented as earned reputation.
        $query = match ($f['sort'] ?? 'featured') {
            'newest' => $query->latest(),
            'rating' => $query->orderByDesc(Review::selectRaw('avg(rating)')->whereColumn('reviewee_id', 'freelancer_profiles.user_id')),
            default => $query->orderByRaw('CASE WHEN featured_until > ? THEN 0 ELSE 1 END', [now()])->latest('updated_at'),
        };

        $profiles = $query->paginate(12)->withQueryString();
        $categories = Category::orderBy('sort_order')->get();
        $shortlisted = $request->user()?->shortlist()->pluck('freelancer_profiles.id')->all() ?? [];

        return view('freelancers.index', compact('profiles', 'categories', 'f', 'shortlisted'));
    }

    public function show(Request $request, FreelancerProfile $profile)
    {
        $viewer = $request->user();
        $isOwner = $viewer?->id === $profile->user_id;
        abort_unless(($profile->is_public && $profile->user->isActive()) || $isOwner || $viewer?->isAdmin(), 404);

        if (! $isOwner) {
            $profile->increment('profile_views');
        }

        $profile->load(['user', 'category', 'skills']);
        $projects = $profile->publishedProjects()->with('category')->get();
        $reviews = Review::where('reviewee_id', $profile->user_id)->with(['reviewer', 'project'])->latest()->take(10)->get();
        $rating = $profile->rating();
        $completed = \App\Models\Project::where('freelancer_user_id', $profile->user_id)->where('status', 'completed')->count();
        $clientJobs = $viewer?->clientProfile ? $viewer->clientProfile->jobPosts()->whereIn('status', ['published'])->get() : collect();
        $isShortlisted = $viewer ? $viewer->shortlist()->whereKey($profile->id)->exists() : false;

        return view('freelancers.show', compact('profile', 'projects', 'reviews', 'rating', 'completed', 'clientJobs', 'isShortlisted', 'isOwner'));
    }

    public function toggleShortlist(Request $request, FreelancerProfile $profile)
    {
        abort_unless($profile->is_public, 404);
        $request->user()->shortlist()->toggle([$profile->id]);

        return back()->with('status', 'Shortlist updated.');
    }

    public function saved(Request $request)
    {
        $profiles = $request->user()->shortlist()->with(['user', 'category', 'skills', 'publishedProjects' => fn ($q) => $q->take(8)])->withCount('publishedProjects')->paginate(12);

        return view('freelancers.saved', compact('profiles'));
    }
}
