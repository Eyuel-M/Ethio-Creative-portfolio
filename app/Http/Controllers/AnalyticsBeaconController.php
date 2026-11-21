<?php

namespace App\Http\Controllers;

use App\Support\Analytics;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/** Receives the page-leave beacon (time on page, scroll depth) and a few client-side interaction events. */
class AnalyticsBeaconController extends Controller
{
    /** Client-side events the page may send (everything else is recorded on the server). */
    public const CLIENT_EVENTS = ['share_open', 'share_click', 'outbound_click', 'cta_click', 'carousel_next', 'filter_open', 'copy_link', 'share_card_download', 'share_card_share'];

    public function beacon(Request $request)
    {
        $d = $request->validate(['pv' => 'required|integer', 't' => 'nullable|integer|min:0|max:86400', 's' => 'nullable|integer|min:0|max:100']);
        $visitor = $request->cookie(Analytics::VISITOR_COOKIE);
        $pv = DB::table('analytics_pageviews')->where('id', $d['pv'])->first();
        if (! $pv || ! $visitor || $pv->visitor_id !== $visitor) {
            return response()->noContent();
        }
        DB::table('analytics_pageviews')->where('id', $pv->id)->update([
            'engaged_seconds' => max($pv->engaged_seconds, min((int) ($d['t'] ?? 0), 3600)),
            'scroll_pct' => max($pv->scroll_pct, (int) ($d['s'] ?? 0)),
        ]);
        $agg = DB::table('analytics_pageviews')->where('session_id', $pv->session_id)->selectRaw('SUM(engaged_seconds) t, MAX(scroll_pct) s')->first();
        DB::table('analytics_sessions')->where('id', $pv->session_id)->update(['engaged_seconds' => (int) $agg->t, 'max_scroll' => (int) $agg->s]);

        return response()->noContent();
    }

    public function event(Request $request)
    {
        $d = $request->validate(['name' => 'required|in:'.implode(',', self::CLIENT_EVENTS), 'pv' => 'nullable|integer', 'label' => 'nullable|string|max:120', 'target' => 'nullable|string|max:300']);
        $visitor = $request->cookie(Analytics::VISITOR_COOKIE);
        $pv = isset($d['pv']) ? DB::table('analytics_pageviews')->where('id', $d['pv'])->first() : null;
        if (! $visitor || ($pv && $pv->visitor_id !== $visitor)) {
            return response()->noContent();
        }
        $request->attributes->add(['analytics_session' => $pv?->session_id, 'analytics_visitor' => $visitor]);
        DB::table('analytics_events')->insert([
            'session_id' => $pv?->session_id, 'visitor_id' => $visitor, 'user_id' => $request->user()?->id, 'name' => $d['name'],
            'subject_type' => $pv?->subject_type, 'subject_id' => $pv?->subject_id,
            'props' => json_encode(array_filter(['label' => $d['label'] ?? null, 'target' => $d['target'] ?? null, 'path' => $pv?->path])),
            'created_at' => now(), 'is_demo' => false,
        ]);

        return response()->noContent();
    }
}
