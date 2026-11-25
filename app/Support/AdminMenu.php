<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/** The admin sidebar: grouped, filtered by the admin's role, with live counts on work queues. */
class AdminMenu
{
    /** @return array<string, list<array{0: string, 1: string, 2: string, 3: string, 4: ?string}>> group => [label, route, icon, section, countKey] */
    public static function definition(): array
    {
        return [
            '' => [
                ['Dashboard', 'admin.overview', 'grid', 'dashboard', null],
            ],
            'Inbox' => [
                ['Reports', 'admin.reports', 'flag', 'reports', 'reports'],
                ['Job moderation', 'admin.jobs', 'briefcase', 'moderation', 'jobs'],
                ['Portfolio review', 'admin.portfolio', 'image', 'moderation', 'portfolio'],
                ['Verification', 'admin.verification', 'verified', 'verification', 'verification'],
                ['Payment issues', 'admin.orders.issues', 'dollar', 'finance', 'payments'],
                ['Data requests', 'admin.data-requests', 'shield', 'data_requests', 'data'],
            ],
            'People' => [
                ['Users', 'admin.users', 'users', 'users', null],
                ['Admin team', 'admin.team', 'lock', 'team', null],
            ],
            'Marketplace' => [
                ['Applications', 'admin.applications', 'file', 'marketplace', null],
                ['Agreements & projects', 'admin.agreements', 'layers', 'agreements', null],
                ['Payment-detail log', 'admin.disclosures', 'bank', 'disclosures', null],
                ['Reviews', 'admin.reviews', 'star', 'reviews', null],
            ],
            'Money' => [
                ['Orders & revenue', 'admin.orders', 'dollar', 'finance', null],
                ['Subscriptions', 'admin.subscriptions', 'refresh', 'finance', null],
                ['Plans & fees', 'admin.products', 'box', 'finance', null],
            ],
            'Insights' => [
                ['Traffic & sources', 'admin.insights.traffic', 'chart', 'insights', null],
                ['Content & behaviour', 'admin.insights.content', 'eye', 'insights', null],
                ['Funnels', 'admin.insights.funnels', 'filter', 'insights', null],
                ['Marketplace health', 'admin.insights.marketplace', 'pencil-ruler', 'insights', null],
                ['Data export', 'admin.insights.export', 'download', 'exports', null],
            ],
            'Publishing' => [
                ['Posts', 'admin.posts', 'pen', 'posts', null],
            ],
            'Content' => [
                ['Categories & skills', 'admin.categories', 'palette', 'content', null],
                ['Homepage & featured', 'admin.featured', 'sparkles', 'content', null],
                ['Announcements', 'admin.announcement', 'megaphone', 'content', null],
            ],
            'System' => [
                ['Settings & switches', 'admin.settings', 'settings', 'settings', null],
                ['System health', 'admin.health', 'monitor', 'health', 'health'],
                ['Audit log', 'admin.audit', 'list', 'audit', null],
            ],
        ];
    }

    /** Items the user may see, grouped. */
    public static function for(User $user): array
    {
        $out = [];
        foreach (self::definition() as $group => $items) {
            $visible = array_values(array_filter($items, fn ($i) => $user->canAdmin($i[3])));
            if ($visible) {
                $out[$group] = $visible;
            }
        }

        return $out;
    }

    /** Open work per queue (cached briefly). */
    public static function counts(): array
    {
        return Cache::remember('admin:counts', 30, fn () => [
            'reports' => DB::table('reports')->where('status', 'open')->count(),
            'jobs' => DB::table('job_posts')->where('status', 'published')->where('published_at', '>=', now()->subDays(2))->whereNotExists(fn ($q) => $q->from('audit_logs')->whereColumn('audit_logs.target_id', 'job_posts.id')->where('action', 'like', 'job.%'))->count(),
            'portfolio' => DB::table('portfolio_projects')->where('status', 'published')->whereNull('reviewed_at')->count(),
            'verification' => DB::table('verification_requests')->where('status', 'pending')->count(),
            'payments' => DB::table('orders')->whereIn('status', ['failed', 'pending_provider'])->where('created_at', '>=', now()->subDays(14))->count(),
            'data' => DB::table('data_requests')->where('status', 'pending')->count(),
            'health' => (int) DB::table('failed_jobs')->count(),
        ]);
    }

    public static function forget(): void
    {
        Cache::forget('admin:counts');
    }
}
