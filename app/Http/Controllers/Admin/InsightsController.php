<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Support\Analytics;
use App\Support\Insights;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/** Visitor analytics and interaction analytics for building a better product. */
class InsightsController extends Controller
{
    private function days(Request $request): int
    {
        return in_array((int) $request->query('days'), [7, 30, 90, 365], true) ? (int) $request->query('days') : 30;
    }

    public function traffic(Request $request)
    {
        $days = $this->days($request);
        $t = Insights::totals($days);
        $d = fn ($k) => Insights::change($t[$k], $t['prev'][$k]);

        return view('admin.insights.traffic', [
            'days' => $days, 't' => $t, 'd' => $d,
            'daily' => Insights::daily('visitors', $days),
            'sources' => Insights::sources($days),
            'quality' => Insights::sourceQuality($days),
            'search' => Insights::sourceDetails($days, 'search', 8),
            'social' => Insights::sourceDetails($days, 'social', 8),
            'referral' => Insights::sourceDetails($days, 'referral', 8),
            'campaigns' => Insights::campaigns($days),
            'landing' => Insights::landingPages($days),
            'pages' => Insights::topPages($days),
            'depth' => Insights::depth($days),
            'scroll' => Insights::scroll($days),
            'devices' => Insights::breakdown($days, 'device'),
            'browsers' => Insights::breakdown($days, 'browser'),
            'countries' => Insights::breakdown($days, 'country', 8),
        ]);
    }

    public function content(Request $request)
    {
        $days = $this->days($request);
        $from = Insights::since($days);
        $ev = fn () => DB::table('analytics_events')->where('created_at', '>=', $from);
        $person = 'COALESCE(CAST(user_id AS CHAR), visitor_id)';

        // Portfolio projects: views, unique viewers, appreciations, time on page, scroll.
        $pv = DB::table('analytics_pageviews')->where('viewed_at', '>=', $from)->where('subject_type', 'portfolio')
            ->selectRaw('subject_id, COUNT(*) views, COUNT(DISTINCT visitor_id) people, AVG(engaged_seconds) t, AVG(scroll_pct) s')->groupBy('subject_id')->orderByDesc('views')->limit(12)->get()->keyBy('subject_id');
        $likes = $ev()->where('name', 'like')->whereIn('subject_id', $pv->keys())->selectRaw('subject_id, COUNT(*) n')->groupBy('subject_id')->pluck('n', 'subject_id');
        $shares = $ev()->whereIn('name', ['share_click', 'copy_link'])->whereIn('subject_id', $pv->keys())->selectRaw('subject_id, COUNT(*) n')->groupBy('subject_id')->pluck('n', 'subject_id');
        $projectNames = DB::table('portfolio_projects')->join('freelancer_profiles', 'freelancer_profiles.id', '=', 'portfolio_projects.freelancer_profile_id')
            ->join('users', 'users.id', '=', 'freelancer_profiles.user_id')->whereIn('portfolio_projects.id', $pv->keys())
            ->get(['portfolio_projects.id', 'portfolio_projects.title', 'users.name'])->keyBy('id');
        $projects = $pv->map(fn ($r, $id) => (object) ['id' => $id, 'title' => $projectNames[$id]->title ?? 'Deleted project', 'by' => $projectNames[$id]->name ?? '',
            'views' => $r->views, 'people' => $r->people, 'time' => (int) round($r->t), 'scroll' => (int) round($r->s), 'likes' => (int) ($likes[$id] ?? 0), 'shares' => (int) ($shares[$id] ?? 0)])->values();

        // Freelancer profiles and jobs.
        $profiles = $ev()->where('name', 'profile_view')->selectRaw("subject_id, COUNT(*) n, COUNT(DISTINCT {$person}) u")->groupBy('subject_id')->orderByDesc('n')->limit(8)->get();
        $profileNames = DB::table('freelancer_profiles')->join('users', 'users.id', '=', 'freelancer_profiles.user_id')->whereIn('freelancer_profiles.id', $profiles->pluck('subject_id'))->pluck('users.name', 'freelancer_profiles.id');
        $jobViews = $ev()->where('name', 'job_view')->selectRaw('subject_id, COUNT(*) n')->groupBy('subject_id')->orderByDesc('n')->limit(8)->get();
        $applyStarts = $ev()->where('name', 'apply_start')->whereIn('subject_id', $jobViews->pluck('subject_id'))->selectRaw('subject_id, COUNT(*) n')->groupBy('subject_id')->pluck('n', 'subject_id');
        $jobNames = DB::table('job_posts')->whereIn('id', $jobViews->pluck('subject_id'))->pluck('title', 'id');
        $jobsAppsDone = DB::table('applications')->whereIn('job_post_id', $jobViews->pluck('subject_id'))->where('created_at', '>=', $from)->selectRaw('job_post_id, COUNT(*) n')->groupBy('job_post_id')->pluck('n', 'job_post_id');

        // Searches.
        $searches = $ev()->where('name', 'search')->get(['props'])->map(fn ($r) => json_decode($r->props, true) ?: []);
        $queries = $searches->groupBy(fn ($p) => ($p['area'] ?? '').'|'.($p['q'] ?? ''))
            ->map(fn ($g, $k) => ['label' => explode('|', $k, 2)[1] ?? '', 'value' => $g->count(), 'extra' => ucfirst(explode('|', $k)[0]).' · avg '.round($g->avg('results')).' results'])
            ->sortByDesc('value')->take(12)->values()->all();
        $zero = $searches->filter(fn ($p) => ($p['results'] ?? 1) == 0)->groupBy(fn ($p) => $p['q'] ?? '')
            ->map(fn ($g, $q) => ['label' => $q, 'value' => $g->count(), 'extra' => ucfirst($g->first()['area'] ?? '')])->sortByDesc('value')->take(10)->values()->all();

        // Every interaction, with how many people did it.
        $events = $ev()->selectRaw("name, COUNT(*) n, COUNT(DISTINCT {$person}) u")->groupBy('name')->orderByDesc('n')->get()
            ->map(fn ($r) => (object) ['name' => $r->name, 'label' => Insights::eventLabel($r->name), 'n' => $r->n, 'u' => $r->u]);

        // Interest by category (views of work, profiles and jobs) vs what's available.
        $catViews = $ev()->whereIn('name', ['portfolio_view', 'profile_view', 'job_view'])->get(['props'])->map(fn ($r) => json_decode($r->props, true)['category'] ?? null)->filter()->countBy();
        $categories = DB::table('categories')->orderBy('sort_order')->get(['id', 'name'])
            ->map(fn ($c) => ['label' => $c->name, 'value' => (int) ($catViews[$c->id] ?? 0)])->sortByDesc('value')->values()->all();

        $sharePlatforms = $ev()->where('name', 'share_click')->get(['props'])->map(fn ($r) => json_decode($r->props, true)['label'] ?? 'Other')->countBy()->sortDesc()
            ->map(fn ($n, $l) => ['label' => $l, 'value' => $n])->values()->all();
        $outbound = $ev()->where('name', 'outbound_click')->get(['props'])->map(fn ($r) => parse_url(json_decode($r->props, true)['target'] ?? '', PHP_URL_HOST) ?: 'unknown')->countBy()->sortDesc()->take(8)
            ->map(fn ($n, $l) => ['label' => $l, 'value' => $n])->values()->all();

        return view('admin.insights.content', compact('days', 'projects', 'profiles', 'profileNames', 'jobViews', 'applyStarts', 'jobNames', 'jobsAppsDone', 'queries', 'zero', 'events', 'categories', 'sharePlatforms', 'outbound'));
    }

    public function funnels(Request $request)
    {
        $days = $this->days($request);
        $from = Insights::since($days);

        // Visitors: each step keeps only people who also reached the step before.
        $visitors = DB::table('analytics_sessions')->where('started_at', '>=', $from)->distinct()->pluck('visitor_id');
        $engaged = DB::table('analytics_sessions')->where('started_at', '>=', $from)->where('pageviews', '>=', 2)->distinct()->pluck('visitor_id')->intersect($visitors);
        $viewed = DB::table('analytics_events')->where('created_at', '>=', $from)->where('name', 'portfolio_view')->distinct()->pluck('visitor_id')->intersect($engaged);
        $signed = DB::table('analytics_events')->where('created_at', '>=', $from)->where('name', 'signup')->distinct()->pluck('visitor_id')->intersect($viewed);
        $site = [['Visited the site', $visitors->count()], ['Looked at 2+ pages', $engaged->count()], ['Viewed someone’s work', $viewed->count()], ['Signed up', $signed->count()]];

        // Members who joined in the period, followed through the marketplace.
        $chain = function (array $steps) {
            $out = [];
            $prev = null;
            foreach ($steps as [$label, $ids]) {
                $ids = collect($ids)->map(fn ($v) => (int) $v)->unique();
                $prev = $prev === null ? $ids : $prev->intersect($ids);
                $out[] = [$label, $prev->count()];
            }

            return $out;
        };
        $fJoined = DB::table('users')->where('created_at', '>=', $from)->where('is_freelancer', true)->pluck('id');
        $fp = DB::table('freelancer_profiles')->whereIn('user_id', $fJoined)->pluck('user_id', 'id');
        $freelancer = $chain([
            ['Joined as a freelancer', $fJoined],
            ['Published a portfolio project', DB::table('portfolio_projects')->whereIn('freelancer_profile_id', $fp->keys())->whereNotNull('published_at')->distinct()->pluck('freelancer_profile_id')->map(fn ($id) => $fp[$id])],
            ['Submitted an application', DB::table('applications')->whereIn('freelancer_profile_id', $fp->keys())->distinct()->pluck('freelancer_profile_id')->map(fn ($id) => $fp[$id])],
            ['Got shortlisted', DB::table('applications')->whereIn('freelancer_profile_id', $fp->keys())->whereIn('status', ['shortlisted', 'discussion', 'negotiation', 'accepted'])->distinct()->pluck('freelancer_profile_id')->map(fn ($id) => $fp[$id])],
            ['Hired (agreement confirmed)', DB::table('projects')->whereIn('freelancer_user_id', $fJoined)->distinct()->pluck('freelancer_user_id')],
        ]);
        $cJoined = DB::table('users')->where('created_at', '>=', $from)->where('is_client', true)->pluck('id');
        $cp = DB::table('client_profiles')->whereIn('user_id', $cJoined)->pluck('user_id', 'id');
        $jobsOf = fn ($q) => $q->pluck('client_profile_id')->unique()->map(fn ($id) => $cp[$id]);
        $client = $chain([
            ['Joined as a client', $cJoined],
            ['Started a job post', $jobsOf(DB::table('job_posts')->whereIn('client_profile_id', $cp->keys()))],
            ['Paid & published a job', $jobsOf(DB::table('job_posts')->whereIn('client_profile_id', $cp->keys())->whereNotNull('published_at'))],
            ['Received applications', $jobsOf(DB::table('job_posts')->whereIn('client_profile_id', $cp->keys())->whereExists(fn ($q) => $q->from('applications')->whereColumn('applications.job_post_id', 'job_posts.id')))],
            ['Shortlisted someone', $jobsOf(DB::table('job_posts')->whereIn('client_profile_id', $cp->keys())->whereExists(fn ($q) => $q->from('applications')->whereColumn('applications.job_post_id', 'job_posts.id')->whereIn('applications.status', ['shortlisted', 'discussion', 'negotiation', 'accepted'])))],
            ['Hired (agreement confirmed)', DB::table('projects')->whereIn('client_user_id', $cJoined)->distinct()->pluck('client_user_id')],
        ]);
        $quality = Insights::sourceQuality($days);

        return view('admin.insights.funnels', compact('days', 'site', 'freelancer', 'client', 'quality'));
    }

    public function marketplace(Request $request)
    {
        $days = $this->days($request);
        $from = Insights::since($days);
        $cats = DB::table('categories')->orderBy('sort_order')->get(['id', 'name']);
        $count = fn ($table, $where = null) => DB::table($table)->when($where, $where)->selectRaw('category_id, COUNT(*) n')->groupBy('category_id')->pluck('n', 'category_id');
        $supply = $count('freelancer_profiles', fn ($q) => $q->where('is_public', true));
        $work = $count('portfolio_projects', fn ($q) => $q->where('status', 'published'));
        $jobs = $count('job_posts', fn ($q) => $q->where('published_at', '>=', $from));
        $apps = DB::table('applications')->join('job_posts', 'job_posts.id', '=', 'applications.job_post_id')->where('applications.created_at', '>=', $from)
            ->selectRaw('job_posts.category_id, COUNT(*) n')->groupBy('job_posts.category_id')->pluck('n', 'category_id');
        $hires = DB::table('projects')->join('applications', 'applications.id', '=', 'projects.application_id')->join('job_posts', 'job_posts.id', '=', 'applications.job_post_id')->where('projects.created_at', '>=', $from)
            ->selectRaw('job_posts.category_id, COUNT(*) n')->groupBy('job_posts.category_id')->pluck('n', 'category_id');
        $rows = $cats->map(fn ($c) => (object) [
            'name' => $c->name, 'freelancers' => (int) ($supply[$c->id] ?? 0), 'work' => (int) ($work[$c->id] ?? 0), 'jobs' => (int) ($jobs[$c->id] ?? 0),
            'apps' => (int) ($apps[$c->id] ?? 0), 'hires' => (int) ($hires[$c->id] ?? 0),
            'per_job' => ($jobs[$c->id] ?? 0) ? round(($apps[$c->id] ?? 0) / $jobs[$c->id], 1) : null,
            'ratio' => ($jobs[$c->id] ?? 0) ? round(($supply[$c->id] ?? 0) / $jobs[$c->id], 1) : null,
        ]);

        $published = DB::table('job_posts')->where('published_at', '>=', $from)->get(['id', 'published_at']);
        $firstApp = DB::table('applications')->whereIn('job_post_id', $published->pluck('id'))->selectRaw('job_post_id, MIN(created_at) f')->groupBy('job_post_id')->pluck('f', 'job_post_id');
        $hoursToFirst = $published->filter(fn ($j) => isset($firstApp[$j->id]))->map(fn ($j) => Carbon::parse($j->published_at)->diffInMinutes(Carbon::parse($firstApp[$j->id])) / 60)->sort()->values();
        $median = $hoursToFirst->count() ? round($hoursToFirst[intdiv($hoursToFirst->count(), 2)], 1) : null;
        $within48 = $published->count() ? round($published->filter(fn ($j) => isset($firstApp[$j->id]) && Carbon::parse($j->published_at)->diffInHours(Carbon::parse($firstApp[$j->id])) <= 48)->count() / $published->count() * 100) : null;
        $hired = DB::table('projects')->join('applications', 'applications.id', '=', 'projects.application_id')->whereIn('applications.job_post_id', $published->pluck('id'))->distinct()->count('applications.job_post_id');

        $active7 = DB::table('users')->where('last_seen_at', '>=', now()->subDays(7))->count();
        $active30 = DB::table('users')->where('last_seen_at', '>=', now()->subDays(30))->count();
        $cohort = DB::table('users')->whereBetween('created_at', [now()->subDays(60), now()->subDays(30)]);
        $retained = (clone $cohort)->count() ? round((clone $cohort)->where('last_seen_at', '>=', now()->subDays(30))->count() / (clone $cohort)->count() * 100) : null;

        $kpis = [
            ['Jobs published', number_format($published->count())],
            ['Got a first application within 48h', $within48 !== null ? $within48.'%' : '—'],
            ['Median time to first application', $median !== null ? $median.' h' : '—'],
            ['Jobs that led to a hire', $published->count() ? round($hired / $published->count() * 100).'%' : '—'],
            ['Active members (7 days)', number_format($active7)],
            ['Active members (30 days)', number_format($active30)],
            ['Weekly / monthly stickiness', $active30 ? round($active7 / $active30 * 100).'%' : '—'],
            ['Still active a month after joining', $retained !== null ? $retained.'%' : '—'],
        ];
        $signups = Insights::dailyCount('users', 'created_at', $days);
        $jobsDaily = Insights::dailyCount('job_posts', 'published_at', $days);

        return view('admin.insights.marketplace', compact('days', 'rows', 'kpis', 'signups', 'jobsDaily'));
    }

    // ── Data export ──

    public const DATASETS = [
        'sessions' => ['Visits', 'One row per visit: when, landing page, source (search / social / direct / referral / email / campaign), device, pages viewed, engaged seconds, scroll depth.'],
        'pageviews' => ['Page views', 'Every page view with the route, what it showed (project / profile / job), position in the visit, time on page and scroll depth.'],
        'events' => ['Interactions', 'Every tracked interaction — views, appreciations, shares, searches, applications, hires, payments — with its details.'],
        'members' => ['Members (pseudonymous)', 'One row per member without names or emails: roles, category, plan, experience, joined, last active, first traffic source.'],
        'portfolio' => ['Portfolio projects', 'Published work with category, freelancer id, views, appreciations, block count and dates.'],
        'jobs' => ['Jobs', 'Job posts with category, budget, status, applications received and whether they led to a hire.'],
        'applications' => ['Applications', 'Proposals with job, freelancer id, price, delivery days, status and dates.'],
    ];

    public function export(Request $request)
    {
        return view('admin.insights.export', ['datasets' => self::DATASETS, 'days' => $this->days($request)]);
    }

    public function download(Request $request, string $dataset)
    {
        abort_unless(isset(self::DATASETS[$dataset]), 404);
        $days = $this->days($request);
        $from = Insights::since($days);
        AuditLog::record('export.downloaded', null, self::DATASETS[$dataset][0].' CSV', ['days' => $days]);

        [$head, $query] = match ($dataset) {
            'sessions' => [['session_id', 'visitor_id', 'member_id', 'started_at', 'landing_path', 'exit_path', 'source', 'source_detail', 'utm_source', 'utm_medium', 'utm_campaign', 'device', 'browser', 'os', 'country', 'new_visitor', 'pageviews', 'events', 'engaged_seconds', 'max_scroll_pct', 'demo'],
                DB::table('analytics_sessions')->where('started_at', '>=', $from)->orderBy('started_at')->select(['id', 'visitor_id', 'user_id', 'started_at', 'landing_path', 'exit_path', 'source', 'source_detail', 'utm_source', 'utm_medium', 'utm_campaign', 'device', 'browser', 'os', 'country', 'is_new_visitor', 'pageviews', 'events', 'engaged_seconds', 'max_scroll', 'is_demo'])],
            'pageviews' => [['id', 'session_id', 'member_id', 'viewed_at', 'path', 'route', 'subject_type', 'subject_id', 'position', 'engaged_seconds', 'scroll_pct', 'demo'],
                DB::table('analytics_pageviews')->where('viewed_at', '>=', $from)->orderBy('id')->select(['id', 'session_id', 'user_id', 'viewed_at', 'path', 'route', 'subject_type', 'subject_id', 'position', 'engaged_seconds', 'scroll_pct', 'is_demo'])],
            'events' => [['id', 'created_at', 'event', 'session_id', 'member_id', 'subject_type', 'subject_id', 'details_json', 'demo'],
                DB::table('analytics_events')->where('created_at', '>=', $from)->orderBy('id')->select(['id', 'created_at', 'name', 'session_id', 'user_id', 'subject_type', 'subject_id', 'props', 'is_demo'])],
            'members' => [['member_id', 'is_freelancer', 'is_client', 'status', 'category', 'plan', 'experience_level', 'years_experience', 'client_type', 'client_verified', 'joined_at', 'last_seen_at', 'first_source', 'first_source_detail'],
                DB::table('users')->leftJoin('freelancer_profiles as fp', 'fp.user_id', '=', 'users.id')->leftJoin('categories as c', 'c.id', '=', 'fp.category_id')->leftJoin('client_profiles as cp', 'cp.user_id', '=', 'users.id')
                    ->where('users.email', 'not like', '%@deleted.invalid')->orderBy('users.id')
                    ->selectRaw('users.id, users.is_freelancer, users.is_client, users.status, c.name, fp.plan, fp.experience_level, fp.years_experience, cp.type, cp.is_verified, users.created_at, users.last_seen_at,
                        (select source from analytics_sessions s where s.user_id = users.id order by started_at limit 1) src,
                        (select source_detail from analytics_sessions s where s.user_id = users.id order by started_at limit 1) srcd')],
            'portfolio' => [['project_id', 'freelancer_profile_id', 'category', 'status', 'published_at', 'views', 'appreciations', 'blocks', 'featured'],
                DB::table('portfolio_projects as p')->leftJoin('categories as c', 'c.id', '=', 'p.category_id')->orderBy('p.published_at')
                    ->selectRaw('p.id, p.freelancer_profile_id, c.name, p.status, p.published_at, p.views, (select count(*) from portfolio_likes l where l.portfolio_project_id = p.id) likes, (select count(*) from portfolio_blocks b where b.portfolio_project_id = p.id) blocks, CASE WHEN p.featured_at IS NULL THEN 0 ELSE 1 END f')],
            'jobs' => [['job_id', 'client_profile_id', 'category', 'status', 'budget_type', 'budget_min', 'budget_max', 'currency', 'experience_level', 'published_at', 'applications', 'hired'],
                DB::table('job_posts as j')->leftJoin('categories as c', 'c.id', '=', 'j.category_id')->where(fn ($q) => $q->where('j.published_at', '>=', $from)->orWhere('j.created_at', '>=', $from))->orderBy('j.created_at')
                    ->selectRaw('j.id, j.client_profile_id, c.name, j.status, j.budget_type, j.budget_min_minor / 100.0, j.budget_max_minor / 100.0, j.currency, j.experience_level, j.published_at, (select count(*) from applications a where a.job_post_id = j.id) apps, (select count(*) from projects pr join applications ap on ap.id = pr.application_id where ap.job_post_id = j.id) hired')],
            'applications' => [['application_id', 'job_id', 'freelancer_profile_id', 'proposed_amount', 'currency', 'estimated_days', 'status', 'created_at', 'updated_at'],
                DB::table('applications')->where('created_at', '>=', $from)->orderBy('created_at')
                    ->selectRaw('id, job_post_id, freelancer_profile_id, proposed_amount_minor / 100.0, currency, estimated_days, status, created_at, updated_at')],
        };

        return response()->streamDownload(function () use ($head, $query) {
            $out = fopen('php://output', 'w');
            fputcsv($out, $head);
            foreach ($query->cursor() as $row) {
                fputcsv($out, array_values((array) $row));
            }
            fclose($out);
        }, "{$dataset}-last-{$days}-days-".now()->format('Y-m-d').'.csv', ['Content-Type' => 'text/csv']);
    }
}
