<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\JobPost;
use App\Models\PortfolioProject;
use App\Notifications\MarketplaceNotification;
use App\Support\AdminMenu;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/** Job and portfolio moderation queues. */
class ModerationController extends Controller
{
    public function jobs(Request $request)
    {
        $tab = $request->query('status', 'new');
        $reviewed = DB::table('audit_logs')->where('action', 'like', 'job.%')->pluck('target_id')->unique();
        $jobs = JobPost::with(['client.user', 'category'])->withCount('applications')
            ->when($request->query('q'), fn ($q, $t) => $q->where(fn ($w) => $w->where('title', 'like', "%{$t}%")->orWhere('id', $t)))
            ->when($tab === 'new', fn ($q) => $q->where('status', 'published')->whereNotIn('id', $reviewed)->where('published_at', '>=', now()->subDays(14)))
            ->when($tab === 'featured', fn ($q) => $q->where('is_featured', true))
            ->when(! in_array($tab, ['new', 'featured', 'all'], true), fn ($q) => $q->where('status', $tab))
            ->latest('published_at')->latest()->paginate(20)->withQueryString();
        $counts = JobPost::selectRaw('status, COUNT(*) n')->groupBy('status')->pluck('n', 'status');
        $newCount = JobPost::where('status', 'published')->whereNotIn('id', $reviewed)->where('published_at', '>=', now()->subDays(14))->count();

        return view('admin.jobs', compact('jobs', 'tab', 'counts', 'newCount'));
    }

    public function moderateJob(Request $request, JobPost $job)
    {
        $data = $request->validate(['action' => 'required|in:approve,remove,feature,unfeature', 'reason' => 'required|string|min:3|max:300']);
        match ($data['action']) {
            'approve' => null,
            'remove' => $job->transitionTo('removed', $request->user()->id, $data['reason']),
            'feature' => $job->forceFill(['is_featured' => true, 'featured_until' => now()->addDays(14)])->save(),
            'unfeature' => $job->forceFill(['is_featured' => false, 'featured_until' => null])->save(),
        };
        AuditLog::record('job.'.$data['action'], $job, $data['reason']);
        if ($data['action'] === 'remove') {
            $job->client?->user?->notify(new MarketplaceNotification('Your job post was removed', "“{$job->title}” was removed by our moderators: {$data['reason']}", route('client.jobs.index'), 'flag'));
        }
        AdminMenu::forget();

        return back()->with('status', 'Job '.$data['action'].'d and logged.');
    }

    public function portfolio(Request $request)
    {
        $tab = $request->query('status', 'review');
        $projects = PortfolioProject::with(['profile.user', 'category'])->withCount('blocks')
            ->when($request->query('q'), fn ($q, $t) => $q->where(fn ($w) => $w->where('title', 'like', "%{$t}%")->orWhere('id', $t)))
            ->when($tab === 'review', fn ($q) => $q->where('status', 'published')->whereNull('reviewed_at'))
            ->when($tab === 'approved', fn ($q) => $q->where('status', 'published')->whereNotNull('reviewed_at'))
            ->when($tab === 'featured', fn ($q) => $q->whereNotNull('featured_at'))
            ->when($tab === 'removed', fn ($q) => $q->where('status', 'removed'))
            ->latest('published_at')->paginate(24)->withQueryString();
        $counts = [
            'review' => PortfolioProject::where('status', 'published')->whereNull('reviewed_at')->count(),
            'approved' => PortfolioProject::where('status', 'published')->whereNotNull('reviewed_at')->count(),
            'featured' => PortfolioProject::whereNotNull('featured_at')->count(),
            'removed' => PortfolioProject::where('status', 'removed')->count(),
        ];

        return view('admin.portfolio', compact('projects', 'tab', 'counts'));
    }

    public function moderatePortfolio(Request $request, PortfolioProject $project)
    {
        $data = $request->validate(['action' => 'required|in:approve,remove,restore,feature,unfeature', 'reason' => 'nullable|string|max:300']);
        abort_if(in_array($data['action'], ['remove', 'restore'], true) && blank($data['reason'] ?? null), 422, 'A reason is required.');
        $me = $request->user()->id;
        match ($data['action']) {
            'approve' => $project->forceFill(['reviewed_at' => now(), 'reviewed_by' => $me])->save(),
            'remove' => $project->forceFill(['status' => 'removed', 'reviewed_at' => now(), 'reviewed_by' => $me, 'moderation_note' => $data['reason'], 'featured_at' => null])->save(),
            'restore' => $project->forceFill(['status' => 'published', 'reviewed_at' => now(), 'reviewed_by' => $me, 'moderation_note' => null])->save(),
            'feature' => $project->forceFill(['featured_at' => now(), 'reviewed_at' => $project->reviewed_at ?? now(), 'reviewed_by' => $project->reviewed_by ?? $me])->save(),
            'unfeature' => $project->forceFill(['featured_at' => null])->save(),
        };
        AuditLog::record('portfolio.'.$data['action'], $project, $data['reason'] ?? null);
        if ($data['action'] === 'remove') {
            $project->profile?->user?->notify(new MarketplaceNotification('A portfolio project was taken down', "“{$project->title}” was removed by our moderators: {$data['reason']}", route('portfolio.index'), 'flag'));
        }
        AdminMenu::forget();

        return back()->with('status', 'Project '.($data['action'] === 'approve' ? 'approved' : $data['action'].'d').'.');
    }

    /** Approve every project on the current review page in one go. */
    public function approveAll(Request $request)
    {
        $ids = $request->validate(['ids' => 'required|array|max:50', 'ids.*' => 'string'])['ids'];
        $n = PortfolioProject::whereIn('id', $ids)->where('status', 'published')->whereNull('reviewed_at')->update(['reviewed_at' => now(), 'reviewed_by' => $request->user()->id]);
        AuditLog::record('portfolio.approve', null, "Bulk approved {$n} projects", ['ids' => $ids]);
        AdminMenu::forget();

        return back()->with('status', "Approved {$n} projects.");
    }
}
