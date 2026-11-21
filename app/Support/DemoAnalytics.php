<?php

namespace App\Support;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Placeholder analytics so the Insights pages have something to show. Every row is flagged is_demo = true and
 * removed by `php artisan demo:clear`. Patterns are plausible for an Ethiopian creative marketplace: mostly mobile,
 * Telegram/Facebook/Instagram heavy, Google for search, slow growth with weekday peaks.
 */
class DemoAnalytics
{
    public static function seed(int $days = 90): void
    {
        mt_srand(4242);
        $projects = DB::table('portfolio_projects')->where('status', 'published')->get(['id', 'category_id'])->all();
        $profiles = DB::table('freelancer_profiles')->where('is_public', true)->get(['id', 'slug', 'category_id'])->all();
        $jobs = DB::table('job_posts')->where('status', 'published')->get(['id', 'category_id'])->all();
        if (! $projects || ! $profiles) {
            return;
        }

        $sources = [ // [source, detail, weight, referrer host]
            ['social', 'Telegram', 18, 't.me'], ['social', 'Facebook', 11, 'facebook.com'], ['social', 'Instagram', 9, 'instagram.com'], ['social', 'LinkedIn', 5, 'linkedin.com'],
            ['social', 'TikTok', 4, 'tiktok.com'], ['social', 'X (Twitter)', 2, 't.co'], ['social', 'Behance', 3, 'behance.net'],
            ['search', 'Google', 20, 'google.com'], ['search', 'Bing', 2, 'bing.com'], ['search', 'ChatGPT', 2, 'chatgpt.com'],
            ['direct', null, 16, null], ['referral', 'eyuelmulat.com', 2, 'eyuelmulat.com'], ['referral', 'addisfortune.news', 1, 'addisfortune.news'],
            ['referral', 'hahu.jobs', 1, 'hahu.jobs'], ['email', 'newsletter', 2, null], ['campaign', 'launch-week', 2, null],
        ];
        $weights = array_sum(array_column($sources, 2));
        $pick = function (array $opts, int $total) {
            $r = mt_rand(1, $total);
            foreach ($opts as $o) {
                if (($r -= $o[2]) <= 0) {
                    return $o;
                }
            }

            return $opts[0];
        };
        $queries = [['talent', 'logo', 6], ['talent', 'branding', 5], ['work', 'packaging', 4], ['jobs', 'design', 5], ['talent', 'photographer', 3], ['work', 'coffee', 3],
            ['talent', 'motion', 2], ['jobs', 'remote', 3], ['talent', 'amharic', 2], ['talent', 'ui designer', 3], ['talent', 'tiktok editor', 2], ['work', 'wedding', 1], ['talent', '3d', 2], ['jobs', 'logo', 2]];
        $zero = ['tiktok editor', 'wedding'];
        $platforms = ['Telegram', 'Telegram', 'Telegram', 'WhatsApp', 'Facebook', 'LinkedIn', 'X'];

        $sessions = $pageviews = $events = [];
        $flush = function () use (&$sessions, &$pageviews, &$events) {
            foreach (array_chunk($sessions, 300) as $c) {
                DB::table('analytics_sessions')->insert($c);
            }
            foreach (array_chunk($pageviews, 500) as $c) {
                DB::table('analytics_pageviews')->insert($c);
            }
            foreach (array_chunk($events, 500) as $c) {
                DB::table('analytics_events')->insert($c);
            }
            $sessions = $pageviews = $events = [];
        };
        $visitors = [];

        DB::transaction(function () use ($days, $projects, $profiles, $jobs, $sources, $weights, $pick, $queries, $zero, $platforms, &$sessions, &$pageviews, &$events, $flush, &$visitors) {
            for ($d = $days; $d >= 0; $d--) {
                $day = now()->subDays($d)->startOfDay();
                $growth = 1 + (($days - $d) / max(1, $days)) * 1.4;
                $n = (int) round((14 + mt_rand(0, 8)) * $growth * ($day->isWeekend() ? 0.7 : 1.0));
                for ($i = 0; $i < $n; $i++) {
                    [$source, $detail, , $host] = $pick($sources, $weights);
                    $returning = $visitors && mt_rand(1, 100) <= 28;
                    $visitor = $returning ? $visitors[array_rand($visitors)] : (string) Str::uuid();
                    if (! $returning) {
                        $visitors[] = $visitor;
                    }
                    $start = $day->copy()->addMinutes(mt_rand(7 * 60, 23 * 60 + 30));
                    $device = mt_rand(1, 100) <= 64 ? 'mobile' : (mt_rand(1, 100) <= 90 ? 'desktop' : 'tablet');
                    $browser = $device === 'mobile' ? (mt_rand(1, 100) <= 78 ? 'Chrome' : (mt_rand(0, 1) ? 'Samsung Internet' : 'Safari')) : (mt_rand(1, 100) <= 80 ? 'Chrome' : (mt_rand(0, 1) ? 'Edge' : 'Firefox'));
                    $os = $device === 'mobile' ? (mt_rand(1, 100) <= 82 ? 'Android' : 'iOS') : (mt_rand(1, 100) <= 75 ? 'Windows' : 'macOS');
                    $country = mt_rand(1, 100) <= 81 ? 'ET' : ['US', 'GB', 'DE', 'AE', 'CA', 'KE', 'SE'][mt_rand(0, 6)];
                    $depthBase = ['search' => 3.2, 'social' => 2.4, 'direct' => 3.8, 'referral' => 3.0, 'email' => 4.0, 'campaign' => 2.2][$source];
                    $pagesN = max(1, min(18, (int) round(-log(max(0.0001, mt_rand(1, 1000) / 1000)) * $depthBase)));
                    $sid = (string) Str::uuid();
                    $t = $start->copy();
                    $engagedTotal = 0;
                    $maxScroll = 0;
                    $landing = null;
                    $exit = '/';
                    $evCount = 0;
                    for ($p = 1; $p <= $pagesN; $p++) {
                        $r = mt_rand(1, 100);
                        $subjectType = $subjectId = $cat = null;
                        if ($p === 1 && $source === 'social' && mt_rand(1, 100) <= 70) {
                            $r = 1; // social posts mostly link straight to a project
                        }
                        if ($r <= 38) {
                            $pr = $projects[array_rand($projects)];
                            [$path, $route, $subjectType, $subjectId, $cat] = ['/portfolio/'.$pr->id, 'portfolio.show', 'portfolio', $pr->id, $pr->category_id];
                        } elseif ($r <= 55) {
                            $pf = $profiles[array_rand($profiles)];
                            [$path, $route, $subjectType, $subjectId, $cat] = ['/freelancers/'.$pf->slug, 'freelancers.show', 'profile', (string) $pf->id, $pf->category_id];
                        } elseif ($r <= 66 && $jobs) {
                            $jb = $jobs[array_rand($jobs)];
                            [$path, $route, $subjectType, $subjectId, $cat] = ['/jobs/'.$jb->id, 'jobs.show', 'job', $jb->id, $jb->category_id];
                        } else {
                            [$path, $route] = [['/', 'home'], ['/explore', 'explore'], ['/freelancers', 'freelancers.index'], ['/jobs', 'jobs.index'], ['/pricing', 'pricing'], ['/how-it-works', 'how'], ['/register', 'register']][mt_rand(0, 6)];
                        }
                        $landing ??= $path;
                        $exit = $path;
                        $engaged = (int) round(($subjectType === 'portfolio' ? 48 : 22) * (0.3 + mt_rand(0, 140) / 100));
                        $scroll = $subjectType === 'portfolio' ? mt_rand(20, 100) : mt_rand(5, 95);
                        $engagedTotal += $engaged;
                        $maxScroll = max($maxScroll, $scroll);
                        $pageviews[] = ['session_id' => $sid, 'visitor_id' => $visitor, 'user_id' => null, 'path' => $path, 'route' => $route, 'subject_type' => $subjectType, 'subject_id' => $subjectId,
                            'position' => $p, 'viewed_at' => $t->copy(), 'engaged_seconds' => $engaged, 'scroll_pct' => $scroll, 'is_demo' => true];
                        $ev = function (string $name, array $props = [], $st = null, $sid2 = null) use (&$events, $sid, $visitor, $t, &$evCount) {
                            $events[] = ['session_id' => $sid, 'visitor_id' => $visitor, 'user_id' => null, 'name' => $name, 'subject_type' => $st, 'subject_id' => $sid2,
                                'props' => $props ? json_encode($props) : null, 'created_at' => $t->copy()->addSeconds(mt_rand(3, 40)), 'is_demo' => true];
                            $evCount++;
                        };
                        if ($subjectType === 'portfolio') {
                            $ev('portfolio_view', ['category' => $cat], 'portfolio', $subjectId);
                            if (mt_rand(1, 100) <= 9) { $ev('like', [], 'portfolio', $subjectId); }
                            if (mt_rand(1, 100) <= 4) { $ev('share_open', [], 'portfolio', $subjectId); $ev('share_click', ['label' => $platforms[array_rand($platforms)]], 'portfolio', $subjectId); }
                        } elseif ($subjectType === 'profile') {
                            $ev('profile_view', ['category' => $cat], 'profile', $subjectId);
                            if (mt_rand(1, 100) <= 6) { $ev('outbound_click', ['target' => ['https://www.behance.net/x', 'https://t.me/x', 'https://instagram.com/x', 'https://linkedin.com/in/x'][mt_rand(0, 3)]], 'profile', $subjectId); }
                        } elseif ($subjectType === 'job') {
                            $ev('job_view', ['category' => $cat], 'job', $subjectId);
                            if (mt_rand(1, 100) <= 12) { $ev('apply_start', [], 'job', $subjectId); if (mt_rand(1, 100) <= 55) { $ev('application_submitted', [], 'application', null); } }
                        } elseif (in_array($route, ['freelancers.index', 'jobs.index', 'explore'], true) && mt_rand(1, 100) <= 35) {
                            [$area, $q] = $pick(array_map(fn ($x) => [$x[0], $x[1], $x[2]], $queries), array_sum(array_column($queries, 2)));
                            $ev('search', ['area' => $area, 'q' => $q, 'results' => in_array($q, $zero, true) ? 0 : mt_rand(2, 14)]);
                        } elseif ($route === 'register' && mt_rand(1, 100) <= 30) {
                            $ev('signup', ['role' => mt_rand(1, 100) <= 62 ? 'freelancer' : 'client']);
                        }
                        $t->addSeconds($engaged + mt_rand(2, 25));
                    }
                    $sessions[] = [
                        'id' => $sid, 'visitor_id' => $visitor, 'user_id' => null, 'started_at' => $start, 'last_seen_at' => $t, 'landing_path' => $landing, 'exit_path' => $exit,
                        'source' => $source, 'source_detail' => $source === 'campaign' ? 'telegram' : $detail, 'referrer_host' => $host,
                        'utm_source' => in_array($source, ['campaign', 'email'], true) ? ($source === 'email' ? 'newsletter' : 'telegram') : null,
                        'utm_medium' => $source === 'email' ? 'email' : ($source === 'campaign' ? 'post' : null), 'utm_campaign' => $source === 'campaign' ? 'launch-week' : ($source === 'email' ? 'november-digest' : null),
                        'device' => $device, 'browser' => $browser, 'os' => $os, 'country' => $country, 'is_new_visitor' => ! $returning,
                        'pageviews' => $pagesN, 'events' => $evCount, 'engaged_seconds' => $engagedTotal, 'max_scroll' => $maxScroll, 'is_demo' => true,
                    ];
                }
                if (count($pageviews) > 3000) {
                    $flush();
                }
            }
            $flush();
        });
    }
}
