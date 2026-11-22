<?php

namespace App\Support;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/** Read-side queries behind the admin dashboard and Insights pages. Works on SQLite and MySQL. */
class Insights
{
    public static function since(int $days): Carbon
    {
        return now()->subDays($days)->startOfDay();
    }

    /** Headline traffic numbers for a period, with the previous period for comparison. */
    public static function totals(int $days): array
    {
        $calc = function (Carbon $from, Carbon $to) {
            $s = DB::table('analytics_sessions')->whereBetween('started_at', [$from, $to]);
            $sessions = (clone $s)->count();
            $row = (clone $s)->selectRaw('COUNT(DISTINCT visitor_id) v, SUM(pageviews) pv, SUM(CASE WHEN pageviews <= 1 THEN 1 ELSE 0 END) bounces, AVG(engaged_seconds) eng, SUM(CASE WHEN is_new_visitor THEN 1 ELSE 0 END) newv')->first();

            return [
                'visitors' => (int) $row->v,
                'sessions' => $sessions,
                'pageviews' => (int) $row->pv,
                'bounce' => $sessions ? round($row->bounces / $sessions * 100, 1) : 0,
                'pages_per_session' => $sessions ? round($row->pv / $sessions, 1) : 0,
                'engaged' => (int) round($row->eng ?? 0),
                'new_visitors' => (int) $row->newv,
            ];
        };
        $now = now();
        $from = self::since($days);
        $cur = $calc($from, $now);
        $cur['prev'] = $calc($from->copy()->subDays($days), $from);

        return $cur;
    }

    public static function change($a, $b): ?int
    {
        return $b ? (int) round(($a - $b) / $b * 100) : null;
    }

    /** Daily series of visitors / sessions / pageviews. */
    public static function daily(string $metric, int $days): array
    {
        $expr = ['visitors' => 'COUNT(DISTINCT visitor_id)', 'sessions' => 'COUNT(*)', 'pageviews' => 'SUM(pageviews)'][$metric];
        $rows = DB::table('analytics_sessions')->where('started_at', '>=', self::since($days))
            ->selectRaw("DATE(started_at) d, {$expr} n")->groupBy('d')->pluck('n', 'd');

        return self::fill($rows, $days);
    }

    public static function dailyCount(string $table, string $col, int $days, ?callable $where = null): array
    {
        $rows = DB::table($table)->when($where, $where)->where($col, '>=', self::since($days))
            ->selectRaw("DATE({$col}) d, COUNT(*) n")->groupBy('d')->pluck('n', 'd');

        return self::fill($rows, $days);
    }

    private static function fill($rows, int $days): array
    {
        $out = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $d = now()->subDays($i);
            $out[] = ['label' => $d->format($days > 90 ? 'M j, Y' : 'M j'), 'value' => (int) ($rows[$d->toDateString()] ?? 0)];
        }
        if ($days > 90) { // weekly buckets for long ranges
            $weeks = [];
            foreach (array_chunk($out, 7) as $chunk) {
                $weeks[] = ['label' => 'Week of '.$chunk[0]['label'], 'value' => array_sum(array_column($chunk, 'value'))];
            }

            return $weeks;
        }

        return $out;
    }

    /** Visits by source, in a fixed order so colours stay stable. */
    public static function sources(int $days): array
    {
        $rows = DB::table('analytics_sessions')->where('started_at', '>=', self::since($days))->selectRaw('source, COUNT(*) n')->groupBy('source')->pluck('n', 'source');
        $out = [];
        foreach (Analytics::SOURCES as $s) {
            $out[Analytics::SOURCE_LABELS[$s]] = (int) ($rows[$s] ?? 0);
        }

        return $out;
    }

    /** Per-source quality: visits, avg pages, avg engaged time, bounce, sign-ups. */
    public static function sourceQuality(int $days): array
    {
        $from = self::since($days);
        $rows = DB::table('analytics_sessions')->where('started_at', '>=', $from)
            ->selectRaw('source, COUNT(*) n, AVG(pageviews) pages, AVG(engaged_seconds) eng, SUM(CASE WHEN pageviews <= 1 THEN 1 ELSE 0 END) b')
            ->groupBy('source')->get()->keyBy('source');
        $signups = DB::table('analytics_events')->join('analytics_sessions', 'analytics_sessions.id', '=', 'analytics_events.session_id')
            ->where('analytics_events.name', 'signup')->where('analytics_events.created_at', '>=', $from)
            ->selectRaw('analytics_sessions.source s, COUNT(*) n')->groupBy('s')->pluck('n', 's');
        $out = [];
        foreach (Analytics::SOURCES as $s) {
            $r = $rows[$s] ?? null;
            $out[] = ['source' => Analytics::SOURCE_LABELS[$s], 'visits' => (int) ($r->n ?? 0), 'pages' => round($r->pages ?? 0, 1), 'engaged' => (int) round($r->eng ?? 0),
                'bounce' => $r && $r->n ? round($r->b / $r->n * 100) : 0, 'signups' => (int) ($signups[$s] ?? 0),
                'conversion' => $r && $r->n ? round(($signups[$s] ?? 0) / $r->n * 100, 1) : 0];
        }

        return $out;
    }

    /** Top named sources within a category (Google, Facebook, Telegram, behance.net …). */
    public static function sourceDetails(int $days, ?string $source = null, int $limit = 10): array
    {
        return DB::table('analytics_sessions')->where('started_at', '>=', self::since($days))->whereNotNull('source_detail')
            ->when($source, fn ($q) => $q->where('source', $source))
            ->selectRaw('source, source_detail, COUNT(*) n')->groupBy('source', 'source_detail')->orderByDesc('n')->limit($limit)->get()
            ->map(fn ($r) => ['label' => $r->source_detail, 'value' => (int) $r->n, 'extra' => Analytics::SOURCE_LABELS[$r->source] ?? $r->source])->all();
    }

    public static function campaigns(int $days): array
    {
        return DB::table('analytics_sessions')->where('started_at', '>=', self::since($days))->whereNotNull('utm_campaign')
            ->selectRaw('utm_campaign c, utm_source s, COUNT(*) n, AVG(pageviews) p')->groupBy('c', 's')->orderByDesc('n')->limit(10)->get()
            ->map(fn ($r) => ['label' => $r->c, 'value' => (int) $r->n, 'sub' => 'via '.($r->s ?: 'unknown').' · '.round($r->p, 1).' pages per visit'])->all();
    }

    /** Readable names for paths like /portfolio/{id} or /jobs/{id}. */
    public static function pathLabel(string $path): string
    {
        static $cache = [];
        if (isset($cache[$path])) {
            return $cache[$path];
        }
        $label = $path;
        if (preg_match('~^/portfolio/([0-9a-z]{26})$~i', $path, $m) && ($t = DB::table('portfolio_projects')->where('id', $m[1])->value('title'))) {
            $label = 'Project: '.$t;
        } elseif (preg_match('~^/jobs/([0-9a-z]{26})$~i', $path, $m) && ($t = DB::table('job_posts')->where('id', $m[1])->value('title'))) {
            $label = 'Job: '.$t;
        } elseif (preg_match('~^/freelancers/([\w-]+)$~', $path, $m) && ($n = DB::table('freelancer_profiles')->join('users', 'users.id', '=', 'freelancer_profiles.user_id')->where('slug', $m[1])->value('users.name'))) {
            $label = 'Profile: '.$n;
        } else {
            $label = ['/' => 'Home', '/explore' => 'Explore', '/jobs' => 'Job board', '/freelancers' => 'Talent directory', '/pricing' => 'Pricing', '/how-it-works' => 'How it works', '/register' => 'Sign-up', '/login' => 'Sign-in'][$path] ?? $path;
        }

        return $cache[$path] = $label;
    }

    public static function landingPages(int $days, int $limit = 10): array
    {
        return DB::table('analytics_sessions')->where('started_at', '>=', self::since($days))
            ->selectRaw('landing_path p, COUNT(*) n, SUM(CASE WHEN pageviews <= 1 THEN 1 ELSE 0 END) b')->groupBy('p')->orderByDesc('n')->limit($limit)->get()
            ->map(fn ($r) => ['label' => self::pathLabel($r->p), 'value' => (int) $r->n, 'extra' => round($r->b / max(1, $r->n) * 100).'% left', 'href' => $r->p])->all();
    }

    public static function topPages(int $days, int $limit = 12): array
    {
        return DB::table('analytics_pageviews')->where('viewed_at', '>=', self::since($days))
            ->selectRaw('path p, COUNT(*) n, AVG(engaged_seconds) t, AVG(scroll_pct) s')->groupBy('p')->orderByDesc('n')->limit($limit)->get()
            ->map(fn ($r) => ['label' => self::pathLabel($r->p), 'value' => (int) $r->n, 'extra' => round($r->t).'s · '.round($r->s).'% scrolled', 'href' => $r->p])->all();
    }

    /** How deep visits go: pages per visit buckets. */
    public static function depth(int $days): array
    {
        $r = DB::table('analytics_sessions')->where('started_at', '>=', self::since($days))->selectRaw(
            'SUM(CASE WHEN pageviews <= 1 THEN 1 ELSE 0 END) a, SUM(CASE WHEN pageviews BETWEEN 2 AND 3 THEN 1 ELSE 0 END) b,
             SUM(CASE WHEN pageviews BETWEEN 4 AND 9 THEN 1 ELSE 0 END) c, SUM(CASE WHEN pageviews >= 10 THEN 1 ELSE 0 END) d')->first();

        return [['label' => '1 page (left right away)', 'value' => (int) $r->a], ['label' => '2–3 pages', 'value' => (int) $r->b], ['label' => '4–9 pages', 'value' => (int) $r->c], ['label' => '10+ pages', 'value' => (int) $r->d]];
    }

    /** How far down pages are read. */
    public static function scroll(int $days): array
    {
        $r = DB::table('analytics_pageviews')->where('viewed_at', '>=', self::since($days))->selectRaw(
            'SUM(CASE WHEN scroll_pct < 25 THEN 1 ELSE 0 END) a, SUM(CASE WHEN scroll_pct BETWEEN 25 AND 49 THEN 1 ELSE 0 END) b,
             SUM(CASE WHEN scroll_pct BETWEEN 50 AND 89 THEN 1 ELSE 0 END) c, SUM(CASE WHEN scroll_pct >= 90 THEN 1 ELSE 0 END) d')->first();

        return [['label' => 'Under a quarter', 'value' => (int) $r->a], ['label' => 'A quarter to half', 'value' => (int) $r->b], ['label' => 'Half to most', 'value' => (int) $r->c], ['label' => 'To the end', 'value' => (int) $r->d]];
    }

    public static function breakdown(int $days, string $col, int $limit = 6): array
    {
        return DB::table('analytics_sessions')->where('started_at', '>=', self::since($days))->whereNotNull($col)
            ->selectRaw("{$col} k, COUNT(*) n")->groupBy('k')->orderByDesc('n')->limit($limit)->get()
            ->map(fn ($r) => ['label' => ucfirst((string) $r->k), 'value' => (int) $r->n])->all();
    }

    /** Count of distinct visitors / users who did an event in the period. */
    public static function eventCount(string $name, int $days, bool $distinctUsers = false): int
    {
        $q = DB::table('analytics_events')->where('name', $name)->where('created_at', '>=', self::since($days));

        return $distinctUsers ? (int) $q->distinct()->count(DB::raw('COALESCE(user_id, visitor_id)')) : $q->count();
    }

    public static function dailyRevenue(int $days): array
    {
        $rows = DB::table('orders')->where('status', 'paid')->where('currency', 'ETB')->where('paid_at', '>=', self::since($days))
            ->selectRaw('DATE(paid_at) d, SUM(amount_minor) n')->groupBy('d')->pluck('n', 'd');

        return self::fill($rows, $days);
    }

    /** Everything the platform records as an interaction, with a human label. */
    public const EVENTS = [
        'signup' => 'Signed up', 'login' => 'Signed in', 'email_verified' => 'Verified email',
        'portfolio_view' => 'Viewed a portfolio project', 'profile_view' => 'Viewed a freelancer profile', 'job_view' => 'Viewed a job',
        'like' => 'Appreciated a project', 'unlike' => 'Removed an appreciation', 'save' => 'Saved a project', 'unsave' => 'Removed a saved project', 'share_open' => 'Opened the share menu', 'share_click' => 'Shared to a platform', 'copy_link' => 'Copied a link',
        'search' => 'Searched', 'filter_used' => 'Used filters', 'outbound_click' => 'Clicked an outside link', 'cta_click' => 'Clicked a call to action',
        'portfolio_published' => 'Published a portfolio project', 'portfolio_saved' => 'Saved a portfolio draft',
        'job_started' => 'Started a job post', 'job_published' => 'Published a job', 'apply_start' => 'Opened an application', 'application_submitted' => 'Submitted an application',
        'shortlisted' => 'Shortlisted an applicant', 'invite_sent' => 'Invited a freelancer', 'message_sent' => 'Sent a message',
        'agreement_proposed' => 'Proposed an agreement', 'agreement_confirmed' => 'Confirmed an agreement', 'project_completed' => 'Completed a project', 'review_left' => 'Left a review',
        'checkout_started' => 'Started a checkout', 'order_paid' => 'Paid for a service', 'verification_requested' => 'Requested verification',
        'share_card_download' => 'Downloaded a portfolio post card', 'share_card_share' => 'Shared a portfolio post card', 'share_card_open' => 'Opened the post card studio',
        'saved_job' => 'Saved a job', 'saved_talent' => 'Saved a freelancer', 'report_filed' => 'Reported something', 'carousel_next' => 'Browsed a carousel', 'filter_open' => 'Opened filters',
    ];

    public static function eventLabel(string $name): string
    {
        return self::EVENTS[$name] ?? ucfirst(str_replace('_', ' ', $name));
    }
}
