<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\FreelancerProfile;
use App\Models\JobPost;
use App\Models\PortfolioProject;
use App\Models\Product;

class PageController extends Controller
{
    public function home()
    {
        $categories = Category::withCount(['freelancers' => fn ($q) => $q->public()])->orderBy('sort_order')->get();
        $featuredTalent = FreelancerProfile::public()->with(['user', 'category', 'skills'])
            ->orderByRaw('CASE WHEN featured_until > ? THEN 0 ELSE 1 END', [now()])
            ->latest('updated_at')->take(8)->get();
        $showcase = PortfolioProject::where('status', 'published')
            ->whereHas('profile', fn ($q) => $q->public())
            ->with('profile.user')->latest('published_at')->take(6)->get();
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
