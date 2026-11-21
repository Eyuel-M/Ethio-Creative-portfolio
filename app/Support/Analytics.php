<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * First-party, privacy-friendly analytics.
 *  - A random visitor id (cookie, 1 year) and a session (30 minutes of inactivity) — no raw IPs, no fingerprinting.
 *  - Each session records how the visitor arrived: search, social, direct, referral, email or campaign (UTM).
 *  - Page views record engaged time and scroll depth (sent by a small beacon when the page is left).
 *  - Interaction events (likes, searches, applications …) are recorded server-side with track().
 */
class Analytics
{
    public const VISITOR_COOKIE = 'ec_vid';

    public const SESSION_MINUTES = 30;

    public const SOURCES = ['search', 'social', 'direct', 'referral', 'email', 'campaign'];

    public const SOURCE_LABELS = ['search' => 'Search engines', 'social' => 'Social media', 'direct' => 'Direct', 'referral' => 'Other websites', 'email' => 'Email', 'campaign' => 'Campaigns (UTM)'];

    private const SEARCH = ['google' => 'Google', 'bing' => 'Bing', 'yahoo' => 'Yahoo', 'duckduckgo' => 'DuckDuckGo', 'yandex' => 'Yandex', 'baidu' => 'Baidu', 'ecosia' => 'Ecosia', 'search.brave' => 'Brave Search', 'startpage' => 'Startpage', 'perplexity' => 'Perplexity', 'chatgpt' => 'ChatGPT'];

    private const SOCIAL = ['facebook' => 'Facebook', 'fb.me' => 'Facebook', 'instagram' => 'Instagram', 't.co' => 'X (Twitter)', 'twitter' => 'X (Twitter)', 'x.com' => 'X (Twitter)', 'linkedin' => 'LinkedIn', 'lnkd.in' => 'LinkedIn', 't.me' => 'Telegram', 'telegram' => 'Telegram', 'tiktok' => 'TikTok', 'youtube' => 'YouTube', 'youtu.be' => 'YouTube', 'whatsapp' => 'WhatsApp', 'wa.me' => 'WhatsApp', 'pinterest' => 'Pinterest', 'reddit' => 'Reddit', 'threads.net' => 'Threads', 'behance' => 'Behance', 'dribbble' => 'Dribbble', 'snapchat' => 'Snapchat'];

    private const EMAIL = ['mail.google', 'outlook', 'mail.yahoo', 'webmail', 'mail.'];

    private const BOTS = '/bot|crawl|spider|slurp|facebookexternalhit|embedly|preview|headless|lighthouse|pingdom|uptime|monitor|curl|wget|python-requests|httpclient|go-http|scrapy|playwright|puppeteer/i';

    public static function enabled(Request $request): bool
    {
        if (! Settings::flag('analytics_tracking') || app()->runningUnitTests() && ! config('analytics.track_in_tests')) {
            return false;
        }
        if (Settings::flag('analytics_respect_dnt') && ($request->header('DNT') === '1' || $request->header('Sec-GPC') === '1')) {
            return false;
        }

        return ! self::isBot((string) $request->userAgent()) && ! $request->session()->has('impersonator_id');
    }

    public static function isBot(string $ua): bool
    {
        return $ua === '' || (bool) preg_match(self::BOTS, $ua);
    }

    /**
     * Where a session came from.
     *
     * @return array{0: string, 1: ?string} [source, detail]
     */
    public static function classify(?string $referrer, array $utm, ?string $ownHost = null): array
    {
        $medium = strtolower((string) ($utm['utm_medium'] ?? ''));
        $src = strtolower((string) ($utm['utm_source'] ?? ''));
        if ($src !== '' || $medium !== '') {
            if (in_array($medium, ['email', 'newsletter', 'e-mail'], true)) {
                return ['email', $src ?: 'email'];
            }
            if (in_array($medium, ['social', 'social-media', 'sm', 'organic_social', 'paid_social'], true)) {
                return ['social', self::matchName(self::SOCIAL, $src) ?? ($src ?: null)];
            }
            if (in_array($medium, ['cpc', 'ppc', 'paid', 'paidsearch', 'organic'], true) && self::matchName(self::SEARCH, $src)) {
                return ['search', self::matchName(self::SEARCH, $src)];
            }

            return ['campaign', $src ?: $medium];
        }

        $host = $referrer ? strtolower((string) parse_url($referrer, PHP_URL_HOST)) : '';
        $host = preg_replace('/^(www\.|m\.|l\.|lm\.|mobile\.)/', '', $host);
        if ($host === '' || ($ownHost && str_ends_with($host, preg_replace('/^www\./', '', strtolower($ownHost))))) {
            return ['direct', null];
        }
        if ($name = self::matchName(self::SEARCH, $host)) {
            return ['search', $name];
        }
        if ($name = self::matchName(self::SOCIAL, $host)) {
            return ['social', $name];
        }
        foreach (self::EMAIL as $needle) {
            if (str_contains($host, $needle)) {
                return ['email', $host];
            }
        }

        return ['referral', $host];
    }

    /**
     * Matches whole domain labels only, so "eyuelmulat.com" never matches "t.co".
     * Keys with a dot ("t.me", "search.brave") match as a domain or domain prefix; plain keys ("google") match a label.
     */
    private static function matchName(array $map, string $host): ?string
    {
        $host = strtolower(trim($host));
        foreach ($map as $needle => $name) {
            $hit = str_contains($needle, '.')
                ? ($host === $needle || str_ends_with($host, '.'.$needle) || str_starts_with($host, $needle.'.') || str_contains($host, '.'.$needle.'.'))
                : ($host === $needle || (bool) preg_match('/(^|\.)'.preg_quote($needle, '/').'\./', $host));
            if ($hit) {
                return $name;
            }
        }

        return null;
    }

    /** @return array{device: string, browser: string, os: string} */
    public static function device(string $ua): array
    {
        $device = preg_match('/ipad|tablet|kindle|silk|playbook|(android(?!.*mobile))/i', $ua) ? 'tablet'
            : (preg_match('/mobi|iphone|ipod|android.*mobile|windows phone|opera mini/i', $ua) ? 'mobile' : 'desktop');
        $browser = match (true) {
            (bool) preg_match('/edg\//i', $ua) => 'Edge',
            (bool) preg_match('/opr\/|opera/i', $ua) => 'Opera',
            (bool) preg_match('/samsungbrowser/i', $ua) => 'Samsung Internet',
            (bool) preg_match('/firefox|fxios/i', $ua) => 'Firefox',
            (bool) preg_match('/chrome|crios/i', $ua) => 'Chrome',
            (bool) preg_match('/safari/i', $ua) => 'Safari',
            default => 'Other',
        };
        $os = match (true) {
            (bool) preg_match('/android/i', $ua) => 'Android',
            (bool) preg_match('/iphone|ipad|ipod/i', $ua) => 'iOS',
            (bool) preg_match('/windows/i', $ua) => 'Windows',
            (bool) preg_match('/mac os/i', $ua) => 'macOS',
            (bool) preg_match('/linux/i', $ua) => 'Linux',
            default => 'Other',
        };

        return compact('device', 'browser', 'os');
    }

    /** The page's subject for content analytics (portfolio project, profile, job). */
    public static function subjectFor(Request $request): array
    {
        $route = $request->route();
        $name = $route?->getName();

        return match ($name) {
            'portfolio.show' => ['portfolio', (string) optional($route->parameter('project'))->getKey() ?: (string) $route->parameter('project')],
            'freelancers.show' => ['profile', (string) optional($route->parameter('profile'))->getKey() ?: (string) $route->parameter('profile')],
            'jobs.show' => ['job', (string) optional($route->parameter('job'))->getKey() ?: (string) $route->parameter('job')],
            default => [null, null],
        };
    }

    /** Session id of the current visit, if one is being tracked. */
    public static function currentSessionId(): ?string
    {
        $req = request();
        if ($req->attributes->has('analytics_session')) {
            return $req->attributes->get('analytics_session');
        }
        // Form posts aren't page views: attach them to the visitor's current visit, if any.
        $visitor = $req->cookie(self::VISITOR_COOKIE);
        $sid = $visitor ? DB::table('analytics_sessions')->where('visitor_id', $visitor)
            ->where('last_seen_at', '>=', now()->subMinutes(self::SESSION_MINUTES))->orderByDesc('last_seen_at')->value('id') : null;
        $req->attributes->set('analytics_session', $sid);

        return $sid;
    }

    /**
     * Record an interaction. Works for signed-in and anonymous visitors; never throws.
     */
    public static function track(string $name, $subject = null, array $props = [], ?int $userId = null): void
    {
        try {
            if (! Settings::flag('analytics_tracking') || session()->has('impersonator_id')) {
                return;
            }
            [$type, $id] = self::subjectKey($subject);
            $sid = self::currentSessionId();
            DB::table('analytics_events')->insert([
                'session_id' => $sid,
                'visitor_id' => request()->cookie(self::VISITOR_COOKIE) ?: request()->attributes->get('analytics_visitor'),
                'user_id' => $userId ?? auth()->id(),
                'name' => Str::limit($name, 50, ''),
                'subject_type' => $type,
                'subject_id' => $id,
                'props' => $props ? json_encode($props) : null,
                'created_at' => now(),
                'is_demo' => false,
            ]);
            if ($sid) {
                DB::table('analytics_sessions')->where('id', $sid)->increment('events');
            }
        } catch (\Throwable $e) {
            report($e);
        }
    }

    /** @return array{0: ?string, 1: ?string} */
    public static function subjectKey($subject): array
    {
        if ($subject === null) {
            return [null, null];
        }
        $map = [
            \App\Models\PortfolioProject::class => 'portfolio', \App\Models\FreelancerProfile::class => 'profile',
            \App\Models\JobPost::class => 'job', \App\Models\Application::class => 'application', \App\Models\User::class => 'user',
            \App\Models\Order::class => 'order', \App\Models\Project::class => 'project', \App\Models\Category::class => 'category',
        ];
        if (is_object($subject) && method_exists($subject, 'getKey')) {
            return [$map[get_class($subject)] ?? Str::snake(class_basename($subject)), (string) $subject->getKey()];
        }

        return [null, null];
    }
}
