<?php

namespace App\Http\Middleware;

use App\Support\Analytics;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/** Records page views and visit sessions for Insights (see App\Support\Analytics). */
class TrackVisit
{
    private const SKIP = ['admin', 'admin/*', 'a/*', 'build/*', 'storage/*', 'webhooks/*', 'sandbox/*', 'up', 'email/*', 'project-files/*'];

    public function handle(Request $request, Closure $next): Response
    {
        if (! $this->shouldTrack($request)) {
            return $this->touchUser($request, $next($request));
        }

        $visitor = $request->cookie(Analytics::VISITOR_COOKIE);
        $isNewVisitor = ! $visitor || ! Str::isUuid($visitor);
        $visitor = $isNewVisitor ? (string) Str::uuid() : $visitor;
        $now = now();
        $path = '/'.ltrim(Str::limit($request->path(), 290, ''), '/');

        try {
            $session = DB::table('analytics_sessions')->where('visitor_id', $visitor)
                ->where('last_seen_at', '>=', $now->copy()->subMinutes(Analytics::SESSION_MINUTES))->orderByDesc('last_seen_at')->first();
            $utm = array_filter($request->only(['utm_source', 'utm_medium', 'utm_campaign']));
            if ($session && $utm && $session->utm_campaign !== ($utm['utm_campaign'] ?? null)) {
                $session = null; // a new campaign click starts a new visit
            }
            if (! $session) {
                [$source, $detail] = Analytics::classify($request->headers->get('referer'), $utm, $request->getHost());
                $d = Analytics::device((string) $request->userAgent());
                $sid = (string) Str::uuid();
                DB::table('analytics_sessions')->insert([
                    'id' => $sid, 'visitor_id' => $visitor, 'user_id' => $request->user()?->id, 'started_at' => $now, 'last_seen_at' => $now,
                    'landing_path' => $path, 'exit_path' => $path, 'source' => $source, 'source_detail' => $detail ? Str::limit($detail, 80, '') : null,
                    'referrer_host' => ($h = parse_url((string) $request->headers->get('referer'), PHP_URL_HOST)) ? Str::limit($h, 120, '') : null,
                    'utm_source' => isset($utm['utm_source']) ? Str::limit($utm['utm_source'], 80, '') : null,
                    'utm_medium' => isset($utm['utm_medium']) ? Str::limit($utm['utm_medium'], 80, '') : null,
                    'utm_campaign' => isset($utm['utm_campaign']) ? Str::limit($utm['utm_campaign'], 120, '') : null,
                    'device' => $d['device'], 'browser' => $d['browser'], 'os' => $d['os'],
                    'country' => ($c = strtoupper((string) $request->header('CF-IPCountry'))) && preg_match('/^[A-Z]{2}$/', $c) && $c !== 'XX' ? $c : null,
                    'is_new_visitor' => $isNewVisitor, 'pageviews' => 0,
                ]);
                $position = 1;
            } else {
                $sid = $session->id;
                $position = $session->pageviews + 1;
            }

            [$type, $id] = Analytics::subjectFor($request);
            $pv = DB::table('analytics_pageviews')->insertGetId([
                'session_id' => $sid, 'visitor_id' => $visitor, 'user_id' => $request->user()?->id, 'path' => $path,
                'route' => $request->route()?->getName(), 'subject_type' => $type, 'subject_id' => $id ?: null,
                'position' => $position, 'viewed_at' => $now,
            ]);
            $request->attributes->add(['analytics_session' => $sid, 'analytics_visitor' => $visitor, 'analytics_pageview' => $pv]);
            View::share('analyticsPageview', $pv);
        } catch (\Throwable $e) {
            report($e);

            return $this->touchUser($request, $next($request));
        }

        $response = $next($request);

        $isPage = $response->getStatusCode() === 200 && str_contains((string) $response->headers->get('Content-Type'), 'text/html');
        if ($isPage) {
            DB::table('analytics_sessions')->where('id', $sid)->update(['pageviews' => DB::raw('pageviews + 1'), 'last_seen_at' => $now, 'exit_path' => $path]);
            if ($uid = $request->user()?->id) {
                DB::table('analytics_sessions')->where('id', $sid)->whereNull('user_id')->update(['user_id' => $uid]);
            }
        } else {
            DB::table('analytics_pageviews')->where('id', $pv)->delete();
        }
        if ($isNewVisitor) {
            $response->headers->setCookie(cookie(Analytics::VISITOR_COOKIE, $visitor, 60 * 24 * 365, '/', null, $request->isSecure(), true, false, 'Lax'));
        }

        return $this->touchUser($request, $response);
    }

    private function shouldTrack(Request $request): bool
    {
        return $request->isMethod('GET') && ! $request->ajax() && ! $request->expectsJson()
            && ! $request->is(...self::SKIP)
            && $request->header('Purpose') !== 'prefetch' && $request->header('Sec-Purpose') !== 'prefetch'
            && Analytics::enabled($request);
    }

    /** Keep users.last_seen_at fresh (at most every 5 minutes). */
    private function touchUser(Request $request, Response $response): Response
    {
        $u = $request->user();
        if ($u && ! $request->session()->has('impersonator_id') && (! $u->last_seen_at || $u->last_seen_at->lt(now()->subMinutes(5)))) {
            $u->forceFill(['last_seen_at' => now()])->saveQuietly();
        }

        return $response;
    }
}
