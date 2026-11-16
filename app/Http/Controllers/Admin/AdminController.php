<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\AuditLog;
use App\Models\FreelancerProfile;
use App\Models\JobPost;
use App\Models\Order;
use App\Models\Project;
use App\Models\Report;
use App\Models\User;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    public function overview()
    {
        $stats = [
            'users' => User::count(),
            'freelancers' => User::where('is_freelancer', true)->count(),
            'clients' => User::where('is_client', true)->count(),
            'new_week' => User::where('created_at', '>=', now()->subWeek())->count(),
            'active_jobs' => JobPost::live()->count(),
            'applications' => Application::count(),
            'projects_active' => Project::whereNotIn('status', ['completed', 'cancelled'])->count(),
            'projects_completed' => Project::where('status', 'completed')->count(),
            'open_reports' => Report::where('status', 'open')->count(),
            'revenue' => Order::where('status', 'paid')->where('currency', 'ETB')->sum('amount_minor'),
            'pending_orders' => Order::where('status', 'pending_provider')->count(),
        ];
        $recent = AuditLog::with('actor')->latest('created_at')->take(8)->get();

        return view('admin.overview', compact('stats', 'recent'));
    }

    public function users(Request $request)
    {
        $users = User::query()
            ->when($request->query('q'), fn ($q, $t) => $q->where(fn ($w) => $w->where('name', 'like', "%{$t}%")->orWhere('email', 'like', "%{$t}%")))
            ->when($request->query('role') === 'freelancer', fn ($q) => $q->where('is_freelancer', true))
            ->when($request->query('role') === 'client', fn ($q) => $q->where('is_client', true))
            ->when($request->query('status'), fn ($q, $s) => $q->where('status', $s))
            ->latest()->paginate(20)->withQueryString();

        return view('admin.users', compact('users'));
    }

    public function userStatus(Request $request, User $user)
    {
        abort_unless($request->user()->hasAdminRole('moderator', 'support'), 403);
        abort_if($user->id === $request->user()->id, 422);
        $data = $request->validate(['status' => 'required|in:active,restricted,suspended,banned', 'reason' => 'required|string|min:5|max:300']);
        $old = $user->status;
        $user->forceFill(['status' => $data['status']])->save();
        AuditLog::record('user.status_changed', $user, $data['reason'], ['from' => $old, 'to' => $data['status']]);

        return back()->with('status', 'User status updated and logged.');
    }

    public function jobs(Request $request)
    {
        $jobs = JobPost::with(['client.user', 'category'])->withCount('applications')
            ->when($request->query('status'), fn ($q, $s) => $q->where('status', $s))
            ->latest()->paginate(20)->withQueryString();

        return view('admin.jobs', compact('jobs'));
    }

    public function moderateJob(Request $request, JobPost $job)
    {
        abort_unless($request->user()->hasAdminRole('moderator'), 403);
        $data = $request->validate(['action' => 'required|in:remove,feature,unfeature', 'reason' => 'required|string|min:5|max:300']);
        match ($data['action']) {
            'remove' => $job->transitionTo('removed', $request->user()->id, $data['reason']),
            'feature' => $job->forceFill(['is_featured' => true, 'featured_until' => now()->addDays(14)])->save(),
            'unfeature' => $job->forceFill(['is_featured' => false, 'featured_until' => null])->save(),
        };
        AuditLog::record('job.'.$data['action'], $job, $data['reason']);

        return back()->with('status', 'Job moderated and logged.');
    }

    public function orders(Request $request)
    {
        abort_unless($request->user()->hasAdminRole('finance'), 403);
        $orders = Order::with(['user', 'product', 'jobPost'])->when($request->query('status'), fn ($q, $s) => $q->where('status', $s))->latest()->paginate(25)->withQueryString();

        return view('admin.orders', compact('orders'));
    }

    public function reports()
    {
        $reports = Report::with('reporter')->latest()->paginate(25);

        return view('admin.reports', compact('reports'));
    }

    public function resolveReport(Request $request, Report $report)
    {
        abort_unless($request->user()->hasAdminRole('moderator'), 403);
        $data = $request->validate(['status' => 'required|in:actioned,dismissed', 'reason' => 'required|string|min:3|max:300']);
        $report->update(['status' => $data['status']]);
        AuditLog::record('report.'.$data['status'], $report, $data['reason']);

        return back()->with('status', 'Report resolved.');
    }

    public function audit()
    {
        $logs = AuditLog::with('actor')->latest('created_at')->paginate(40);

        return view('admin.audit', compact('logs'));
    }
}
