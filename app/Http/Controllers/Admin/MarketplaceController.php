<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\AuditLog;
use App\Models\Project;
use App\Models\Review;
use Illuminate\Http\Request;

/** Read-mostly views of the marketplace: applications, agreements/projects, payment-detail log, reviews. */
class MarketplaceController extends Controller
{
    public function applications(Request $request)
    {
        $tab = $request->query('status', '');
        $apps = Application::with(['jobPost.client', 'freelancer.user'])
            ->when($tab, fn ($q, $s) => $q->where('status', $s))
            ->latest()->paginate(25)->withQueryString();
        $counts = Application::selectRaw('status, COUNT(*) n')->groupBy('status')->pluck('n', 'status');

        return view('admin.applications', compact('apps', 'tab', 'counts'));
    }

    public function agreements(Request $request)
    {
        $tab = $request->query('status', '');
        $projects = Project::with(['client', 'freelancer', 'agreement'])
            ->when($tab, fn ($q, $s) => $q->where('status', $s))
            ->when($request->query('q'), fn ($q, $t) => $q->where(fn ($w) => $w->where('title', 'like', "%{$t}%")->orWhere('id', $t)))
            ->latest()->paginate(25)->withQueryString();
        $counts = Project::selectRaw('status, COUNT(*) n')->groupBy('status')->pluck('n', 'status');

        return view('admin.agreements.index', compact('projects', 'tab', 'counts'));
    }

    public function showAgreement(Project $project)
    {
        $project->load(['client', 'freelancer', 'milestones', 'events', 'files', 'reviews.reviewer', 'application.agreements.confirmations.user', 'application.agreements.author', 'application.jobPost']);
        $disclosures = AuditLog::with('actor')->where('action', 'payment_details.revealed')->where('target_type', 'Project')->where('target_id', $project->id)->latest('created_at')->get();

        return view('admin.agreements.show', compact('project', 'disclosures'));
    }

    public function disclosures()
    {
        $logs = AuditLog::with('actor')->where('action', 'payment_details.revealed')->latest('created_at')->paginate(40);
        $projects = Project::with(['client', 'freelancer'])->whereIn('id', $logs->pluck('target_id'))->get()->keyBy('id');

        return view('admin.disclosures', compact('logs', 'projects'));
    }

    public function reviews(Request $request)
    {
        $tab = $request->query('status', 'visible');
        $reviews = Review::with(['reviewer', 'project'])->when($tab === 'hidden', fn ($q) => $q->whereNotNull('hidden_at'), fn ($q) => $q->whereNull('hidden_at'))
            ->when($request->query('rating'), fn ($q, $r) => $q->where('rating', $r))->latest()->paginate(25)->withQueryString();
        $reviewees = \App\Models\User::whereIn('id', $reviews->pluck('reviewee_id'))->get()->keyBy('id');

        return view('admin.reviews', compact('reviews', 'tab', 'reviewees'));
    }

    public function toggleReview(Request $request, Review $review)
    {
        $data = $request->validate(['reason' => 'required|string|min:3|max:300']);
        $hide = $review->hidden_at === null;
        $review->forceFill(['hidden_at' => $hide ? now() : null, 'hidden_reason' => $hide ? $data['reason'] : null])->save();
        AuditLog::record($hide ? 'review.hidden' : 'review.restored', $review, $data['reason']);

        return back()->with('status', $hide ? 'Review hidden from profiles.' : 'Review restored.');
    }
}
