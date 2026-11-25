<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Order;
use App\Models\User;
use App\Support\AdminMenu;
use App\Support\Insights;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function __invoke()
    {
        $me = auth()->user();
        $counts = AdminMenu::counts();
        $from = now()->subDays(30);
        $prev = now()->subDays(60);
        $change = fn ($a, $b) => $b ? (int) round(($a - $b) / $b * 100) : null;
        $period = fn ($table, $col = 'created_at', $where = null) => [
            DB::table($table)->when($where, $where)->where($col, '>=', $from)->count(),
            DB::table($table)->when($where, $where)->whereBetween($col, [$prev, $from])->count(),
        ];

        [$users, $usersPrev] = $period('users');
        [$jobs, $jobsPrev] = $period('job_posts', 'published_at');
        [$apps, $appsPrev] = $period('applications');
        [$agreements, $agreementsPrev] = $period('projects');
        $rev = fn ($a, $b) => (int) Order::where('status', 'paid')->where('currency', 'ETB')->whereBetween('paid_at', [$a, $b])->sum('amount_minor');
        $revenue = $rev($from, now());
        $revenuePrev = $rev($prev, $from);
        $traffic = Insights::totals(30);

        $kpis = [
            ['Visitors', number_format($traffic['visitors']), $change($traffic['visitors'], $traffic['prev']['visitors']), 'admin.insights.traffic', 'insights'],
            ['New members', number_format($users), $change($users, $usersPrev), 'admin.users', 'users'],
            ['Jobs published', number_format($jobs), $change($jobs, $jobsPrev), 'admin.jobs', 'moderation'],
            ['Applications', number_format($apps), $change($apps, $appsPrev), 'admin.applications', 'marketplace'],
            ['Projects started', number_format($agreements), $change($agreements, $agreementsPrev), 'admin.agreements', 'agreements'],
            ['Revenue', \App\Support\Money::format($revenue, 'ETB'), $change($revenue, $revenuePrev), 'admin.orders', 'finance'],
        ];

        // Who's joining: which path members picked, and who's still deciding.
        $members = [
            'freelancer' => User::where('is_freelancer', true)->whereNull('admin_role')->count(),
            'client' => User::where('is_client', true)->whereNull('admin_role')->count(),
            'member' => User::undecided()->count(),
            'member_active' => User::undecided()->where('last_seen_at', '>=', now()->subDays(7))->count(),
        ];

        $oldest = fn ($table, $where) => ($d = DB::table($table)->where($where)->min('created_at')) ? \Illuminate\Support\Carbon::parse($d)->diffForHumans(null, true) : null;
        $queues = [
            ['Open reports', $counts['reports'], $oldest('reports', ['status' => 'open']), 'admin.reports', 'reports', 'flag'],
            ['Portfolio to review', $counts['portfolio'], null, 'admin.portfolio', 'moderation', 'image'],
            ['New jobs to check', $counts['jobs'], null, 'admin.jobs', 'moderation', 'briefcase'],
            ['Verification requests', $counts['verification'], $oldest('verification_requests', ['status' => 'pending']), 'admin.verification', 'verification', 'verified'],
            ['Payment issues', $counts['payments'], null, 'admin.orders.issues', 'finance', 'dollar'],
            ['Data requests', $counts['data'], $oldest('data_requests', ['status' => 'pending']), 'admin.data-requests', 'data_requests', 'shield'],
            ['Failed background jobs', $counts['health'], null, 'admin.health', 'health', 'monitor'],
        ];
        $queues = array_values(array_filter($queues, fn ($q) => $me->canAdmin($q[4])));

        $visitorsDaily = Insights::daily('visitors', 30);
        $signupsDaily = Insights::dailyCount('users', 'created_at', 30);
        $recentUsers = User::latest()->take(6)->get();
        $activity = AuditLog::with('actor')->whereHas('actor', fn ($q) => $q->whereNotNull('admin_role'))
            ->whereNotIn('action', ['admin.login', 'admin.verified'])->latest('created_at')->take(8)->get();

        return view('admin.dashboard', compact('kpis', 'members', 'queues', 'visitorsDaily', 'signupsDaily', 'recentUsers', 'activity', 'traffic'));
    }
}
