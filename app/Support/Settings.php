<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Platform settings editable from the admin console (announcement, feature switches …).
 * Values are JSON, cached, and always fall back to the defaults below.
 */
class Settings
{
    public const DEFAULTS = [
        'announcement' => ['active' => false, 'text' => '', 'link' => null, 'link_label' => null, 'tone' => 'info', 'audience' => 'all'],
        'flags' => [
            'registrations_open' => true,
            'job_posting_open' => true,
            'portfolio_publishing_open' => true,
            'analytics_tracking' => true,
            'analytics_respect_dnt' => true,
        ],
    ];

    public const FLAG_LABELS = [
        'registrations_open' => ['New sign-ups', 'When off, the sign-up page explains that registrations are paused.'],
        'job_posting_open' => ['Job posting', 'When off, clients cannot start new job posts.'],
        'portfolio_publishing_open' => ['Portfolio publishing', 'When off, freelancers can save drafts but not publish.'],
        'analytics_tracking' => ['Visitor analytics', 'Record anonymous visits and interactions for Insights.'],
        'analytics_respect_dnt' => ['Respect “Do Not Track”', 'Skip analytics for browsers that send the DNT / GPC signal.'],
    ];

    public static function get(string $key, $default = null)
    {
        $all = Cache::rememberForever('settings:all', function () {
            try {
                return DB::table('settings')->pluck('value', 'key')->map(fn ($v) => json_decode($v, true))->all();
            } catch (\Throwable) {
                return [];
            }
        });
        $base = self::DEFAULTS[$key] ?? $default;
        $value = $all[$key] ?? null;

        return is_array($base) && is_array($value) ? array_replace($base, $value) : ($value ?? $base);
    }

    public static function set(string $key, $value): void
    {
        DB::table('settings')->updateOrInsert(['key' => $key], ['value' => json_encode($value), 'updated_by' => auth()->id(), 'updated_at' => now(), 'created_at' => now()]);
        Cache::forget('settings:all');
    }

    public static function flag(string $name): bool
    {
        return (bool) (self::get('flags')[$name] ?? false);
    }
}
