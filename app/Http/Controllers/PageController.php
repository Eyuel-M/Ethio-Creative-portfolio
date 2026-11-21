<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\FreelancerProfile;
use App\Models\JobPost;
use App\Models\PortfolioProject;
use App\Models\Product;

class PageController extends Controller
{
    public function home(\Illuminate\Http\Request $request)
    {
        // Signed-in users (freelancer or client) get the portfolio discovery feed instead of the landing page.
        if ($request->user()) {
            return app(FeedController::class)->index($request);
        }

        $categories = Category::withCount(['freelancers' => fn ($q) => $q->public()])->orderBy('sort_order')->get();
        $featuredTalent = FreelancerProfile::public()->with(['user', 'category', 'skills'])
            ->orderByRaw('CASE WHEN featured_until > ? THEN 0 ELSE 1 END', [now()])
            ->latest('updated_at')->take(8)->get();
        // Bento showcase: strongest recent work (views + likes), covers only.
        $showcase = PortfolioProject::discoverable()->whereNotNull('cover_path')
            ->with(['profile.user', 'category'])->withCount('likes')
            ->orderByRaw('CASE WHEN featured_at IS NULL THEN 1 ELSE 0 END')->orderByDesc('featured_at') // admin-featured work leads
            ->orderByRaw('views + 20 * (select count(*) from portfolio_likes pl where pl.portfolio_project_id = portfolio_projects.id) desc')
            ->take(7)->get();
        $jobs = JobPost::live()->with(['client.user', 'category'])->orderByDesc('is_featured')->latest('published_at')->take(4)->get();
        $stats = [
            'freelancers' => FreelancerProfile::public()->count(),
            'projects' => PortfolioProject::where('status', 'published')->count(),
            'jobs' => JobPost::live()->count(),
        ];

        return view('public.home', compact('categories', 'featuredTalent', 'showcase', 'jobs', 'stats'));
    }

    public function pricing()
    {
        $products = Product::where('is_active', true)->orderBy('sort_order')->get()->groupBy('audience');

        return view('public.pricing', compact('products'));
    }

    public function how()
    {
        return view('public.how');
    }
}
