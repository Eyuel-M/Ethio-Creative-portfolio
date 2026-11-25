<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminNote;
use App\Models\Application;
use App\Models\AuditLog;
use App\Models\Entitlement;
use App\Models\JobPost;
use App\Models\Order;
use App\Models\Project;
use App\Models\Report;
use App\Models\User;
use App\Notifications\MarketplaceNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $f = $request->validate([
            'q' => 'nullable|string|max:120', 'role' => 'nullable|in:freelancer,client,admin,member', 'status' => 'nullable|in:active,restricted,suspended,banned,deleted',
            'plan' => 'nullable|in:free,professional', 'sort' => 'nullable|in:newest,oldest,last_seen,name',
        ]);
        $users = User::query()->with(['freelancerProfile', 'clientProfile'])
            ->when($f['q'] ?? null, fn ($q, $t) => $q->where(fn ($w) => $w->where('name', 'like', "%{$t}%")->orWhere('email', 'like', "%{$t}%")->orWhere('id', $t)))
            ->when(($f['role'] ?? null) === 'freelancer', fn ($q) => $q->where('is_freelancer', true))
            ->when(($f['role'] ?? null) === 'client', fn ($q) => $q->where('is_client', true))
            ->when(($f['role'] ?? null) === 'admin', fn ($q) => $q->whereNotNull('admin_role'))
            // Signed up but hasn't picked freelancer or client yet.
            ->when(($f['role'] ?? null) === 'member', fn ($q) => $q->undecided())
            ->when($f['status'] ?? null, fn ($q, $s) => $q->where('status', $s))
            ->when($f['plan'] ?? null, fn ($q, $p) => $q->whereHas('freelancerProfile', fn ($w) => $w->where('plan', $p)))
            ->tap(fn ($q) => match ($f['sort'] ?? 'newest') {
                'oldest' => $q->oldest(),
                'last_seen' => $q->orderByDesc('last_seen_at'),
                'name' => $q->orderBy('name'),
                default => $q->latest(),
            })
            ->paginate(25)->withQueryString();
        $statusCounts = User::selectRaw('status, COUNT(*) n')->groupBy('status')->pluck('n', 'status');

        return view('admin.users.index', compact('users', 'f', 'statusCounts'));
    }

    public function show(User $user)
    {
        $user->load(['freelancerProfile.category', 'freelancerProfile.portfolioProjects', 'clientProfile', 'adminNotes.author']);
        $fp = $user->freelancerProfile;
        $cp = $user->clientProfile;

        $jobs = $cp ? JobPost::where('client_profile_id', $cp->id)->withCount('applications')->latest()->take(10)->get() : collect();
        $applications = $fp ? Application::with('jobPost')->where('freelancer_profile_id', $fp->id)->latest()->take(10)->get() : collect();
        $projects = Project::with(['client', 'freelancer'])->where(fn ($q) => $q->where('client_user_id', $user->id)->orWhere('freelancer_user_id', $user->id))->latest()->take(10)->get();
        $orders = Order::with('product')->where('user_id', $user->id)->latest()->take(10)->get();
        $entitlements = Entitlement::with('product')->where('user_id', $user->id)->where('ends_at', '>', now())->get();

        $targets = collect([['profile', $fp?->id], ['profile', $fp?->slug]])
            ->merge($jobs->map(fn ($j) => ['job', $j->id]))
            ->merge(($fp?->portfolioProjects ?? collect())->map(fn ($p) => ['portfolio', $p->id]))->filter(fn ($t) => $t[1]);
        $reportsAgainst = Report::with('reporter')->where(function ($q) use ($targets) {
            $q->whereRaw('1 = 0');
            foreach ($targets as [$type, $id]) {
                $q->orWhere(fn ($w) => $w->where('reportable_type', $type)->where('reportable_id', (string) $id));
            }
        })->latest()->get();
        $reportsBy = Report::where('reporter_id', $user->id)->count();
        $audit = AuditLog::with('actor')->where(fn ($q) => $q->where(fn ($w) => $w->where('target_type', 'User')->where('target_id', (string) $user->id))->orWhere('actor_id', $user->id))
            ->latest('created_at')->take(15)->get();

        $analytics = [
            'sessions' => DB::table('analytics_sessions')->where('user_id', $user->id)->count(),
            'pageviews' => DB::table('analytics_pageviews')->where('user_id', $user->id)->count(),
            'first' => DB::table('analytics_sessions')->where('user_id', $user->id)->orderBy('started_at')->first(),
            'events' => DB::table('analytics_events')->where('user_id', $user->id)->latest('created_at')->take(12)->get(),
        ];
        $verification = \App\Models\VerificationRequest::where('user_id', $user->id)->latest()->first();

        return view('admin.users.show', compact('user', 'fp', 'cp', 'jobs', 'applications', 'projects', 'orders', 'entitlements', 'reportsAgainst', 'reportsBy', 'audit', 'analytics', 'verification'));
    }

    public function status(Request $request, User $user)
    {
        abort_unless($request->user()->canAdmin('users_manage'), 403);
        abort_if($user->id === $request->user()->id, 422, 'You cannot change your own status.');
        abort_if($user->admin_role === 'super_admin' && $request->user()->admin_role !== 'super_admin', 403);
        $data = $request->validate(['status' => 'required|in:active,restricted,suspended,banned', 'reason' => 'required|string|min:3|max:300']);
        $old = $user->status;
        $user->forceFill(['status' => $data['status']])->save();
        if (in_array($data['status'], ['suspended', 'banned'], true)) {
            DB::table('sessions')->where('user_id', $user->id)->delete(); // sign them out everywhere
        }
        AuditLog::record('user.status_changed', $user, $data['reason'], ['from' => $old, 'to' => $data['status']]);

        return back()->with('status', "Status changed to {$data['status']} and logged.");
    }

    public function note(Request $request, User $user)
    {
        $data = $request->validate(['body' => 'required|string|min:2|max:2000']);
        AdminNote::create(['user_id' => $user->id, 'author_id' => $request->user()->id, 'body' => $data['body']]);
        AuditLog::record('user.note_added', $user);

        return back()->with('status', 'Note added.');
    }

    public function verifyClient(Request $request, User $user)
    {
        abort_unless($request->user()->canAdmin('verification'), 403);
        abort_unless($user->clientProfile, 404);
        $data = $request->validate(['verified' => 'required|boolean', 'reason' => 'required|string|min:3|max:300']);
        $user->clientProfile->forceFill(['is_verified' => (bool) $data['verified']])->save();
        AuditLog::record($data['verified'] ? 'user.client_verified' : 'user.client_unverified', $user, $data['reason']);
        if ($data['verified']) {
            $user->notify(new MarketplaceNotification('Your business is verified', 'A verified badge now appears on your jobs and profile.', route('dashboard'), 'verified'));
        }

        return back()->with('status', $data['verified'] ? 'Client verified.' : 'Verification removed.');
    }

    public function plan(Request $request, User $user)
    {
        abort_unless($request->user()->canAdmin('finance') || $request->user()->canAdmin('users_manage'), 403);
        abort_unless($user->freelancerProfile, 404);
        $data = $request->validate(['plan' => 'required|in:free,professional', 'reason' => 'required|string|min:3|max:300']);
        $old = $user->freelancerProfile->plan;
        $user->freelancerProfile->forceFill(['plan' => $data['plan']])->save();
        AuditLog::record('user.plan_changed', $user, $data['reason'], ['from' => $old, 'to' => $data['plan']]);

        return back()->with('status', 'Plan updated.');
    }

    public function feature(Request $request, User $user)
    {
        abort_unless($request->user()->canAdmin('content'), 403);
        abort_unless($user->freelancerProfile, 404);
        $data = $request->validate(['days' => 'required|integer|min:0|max:90', 'reason' => 'required|string|min:3|max:300']);
        $user->freelancerProfile->forceFill(['featured_until' => $data['days'] ? now()->addDays((int) $data['days']) : null])->save();
        AuditLog::record($data['days'] ? 'profile.feature' : 'profile.unfeature', $user, $data['reason'], ['days' => (int) $data['days']]);

        return back()->with('status', $data['days'] ? "Featured for {$data['days']} days." : 'No longer featured.');
    }

    // ── View as user (read-only) ──

    public function impersonate(Request $request, User $user)
    {
        abort_unless($request->user()->canAdmin('impersonate'), 403);
        abort_if($user->isAdmin() || $user->id === $request->user()->id, 422, 'Admins cannot be impersonated.');
        $data = $request->validate(['reason' => 'required|string|min:3|max:300']);
        $admin = $request->user();
        AuditLog::record('admin.impersonation_started', $user, $data['reason']);
        Auth::login($user);
        $request->session()->put('impersonator_id', $admin->id);
        $request->session()->put('impersonator_return', route('admin.users.show', $user));

        return redirect()->route('dashboard')->with('status', "You are viewing the site as {$user->name}. Everything is read-only.");
    }

    public function stopImpersonating(Request $request)
    {
        $adminId = $request->session()->pull('impersonator_id');
        $return = $request->session()->pull('impersonator_return', route('admin.overview'));
        abort_unless($adminId, 404);
        $viewed = $request->user();
        Auth::login(User::findOrFail($adminId));
        $request->session()->put('admin_verified_at', now()->timestamp);
        AuditLog::record('admin.impersonation_stopped', $viewed);

        return redirect($return)->with('status', 'Back to your admin account.');
    }
}
