<?php

namespace Tests\Feature;

use App\Support\Analytics;
use Illuminate\Support\Facades\DB;

class AnalyticsTest extends MarketplaceTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['analytics.track_in_tests' => true]);
    }

    public function test_sources_are_classified(): void
    {
        $this->assertSame(['search', 'Google'], Analytics::classify('https://www.google.com/search?q=x', []));
        $this->assertSame(['social', 'Telegram'], Analytics::classify('https://t.me/somechannel', []));
        $this->assertSame(['social', 'Facebook'], Analytics::classify('https://l.facebook.com/l.php?u=x', []));
        $this->assertSame(['social', 'X (Twitter)'], Analytics::classify('https://t.co/abc', []));
        $this->assertSame(['direct', null], Analytics::classify(null, []));
        $this->assertSame(['direct', null], Analytics::classify('https://ethio.test/jobs', [], 'ethio.test'));
        $this->assertSame(['referral', 'eyuelmulat.com'], Analytics::classify('https://www.eyuelmulat.com/work', []));
        $this->assertSame(['email', 'newsletter'], Analytics::classify(null, ['utm_source' => 'newsletter', 'utm_medium' => 'email']));
        $this->assertSame(['campaign', 'telegram'], Analytics::classify('https://t.me/x', ['utm_source' => 'telegram', 'utm_campaign' => 'launch']));
        $this->assertSame('mobile', Analytics::device('Mozilla/5.0 (Linux; Android 13) AppleWebKit Chrome/120 Mobile Safari')['device']);
    }

    public function test_visits_are_recorded_with_source_depth_and_engagement(): void
    {
        $ua = ['HTTP_USER_AGENT' => 'Mozilla/5.0 (Windows NT 10.0) Chrome/120 Safari/537.36', 'HTTP_REFERER' => 'https://t.me/ethiodesign'];
        $first = $this->withServerVariables($ua)->get(route('jobs.index'));
        $first->assertOk()->assertSee('name="ec-pv"', false);
        $vid = $first->getCookie(Analytics::VISITOR_COOKIE, true)?->getValue();
        $this->assertNotNull($vid);

        $session = DB::table('analytics_sessions')->first();
        $this->assertSame('social', $session->source);
        $this->assertSame('Telegram', $session->source_detail);
        $this->assertSame('desktop', $session->device);

        // Second page in the same visit: same session, depth 2.
        $this->withCookie(Analytics::VISITOR_COOKIE, $vid)->withServerVariables($ua)->get(route('freelancers.index'))->assertOk();
        $this->assertSame(1, DB::table('analytics_sessions')->count());
        $this->assertSame(2, (int) DB::table('analytics_sessions')->value('pageviews'));

        // Page-leave beacon: engaged time and scroll depth.
        $pv = DB::table('analytics_pageviews')->orderByDesc('id')->first();
        $this->withCookie(Analytics::VISITOR_COOKIE, $vid)->post(route('analytics.beacon'), ['pv' => $pv->id, 't' => 42, 's' => 80])->assertNoContent();
        $this->assertSame(42, (int) DB::table('analytics_pageviews')->where('id', $pv->id)->value('engaged_seconds'));
        $this->assertSame(80, (int) DB::table('analytics_sessions')->value('max_scroll'));

        // A different visitor can't write to someone else's page view.
        $this->withCookie(Analytics::VISITOR_COOKIE, (string) \Illuminate\Support\Str::uuid())->post(route('analytics.beacon'), ['pv' => $pv->id, 't' => 999])->assertNoContent();
        $this->assertSame(42, (int) DB::table('analytics_pageviews')->where('id', $pv->id)->value('engaged_seconds'));
    }

    public function test_bots_admin_pages_and_do_not_track_are_skipped(): void
    {
        $this->withServerVariables(['HTTP_USER_AGENT' => 'Googlebot/2.1'])->get(route('jobs.index'))->assertOk();
        $this->withServerVariables(['HTTP_USER_AGENT' => 'Mozilla/5.0 Chrome', 'HTTP_DNT' => '1'])->get(route('jobs.index'))->assertOk();
        $this->assertSame(0, DB::table('analytics_sessions')->count());
    }

    public function test_interactions_are_recorded(): void
    {
        $f = $this->freelancer();
        $c = $this->client();
        $job = $this->job($c);
        $this->withServerVariables(['HTTP_USER_AGENT' => 'Mozilla/5.0 Chrome'])->actingAs($f)->get(route('jobs.show', $job))->assertOk();
        $this->actingAs($f)->get(route('jobs.index', ['q' => 'nothing-matches-this']))->assertOk();
        $this->assertDatabaseHas('analytics_events', ['name' => 'job_view', 'subject_type' => 'job', 'subject_id' => $job->id, 'user_id' => $f->id]);
        $search = DB::table('analytics_events')->where('name', 'search')->first();
        $this->assertSame(['area' => 'jobs', 'q' => 'nothing-matches-this', 'results' => 0], json_decode($search->props, true));

        $this->withCookie(Analytics::VISITOR_COOKIE, (string) \Illuminate\Support\Str::uuid())->post(route('analytics.event'), ['name' => 'share_click', 'label' => 'Telegram'])->assertNoContent();
        $this->post(route('analytics.event'), ['name' => 'not_allowed'])->assertSessionHasErrors('name');
    }

    public function test_demo_analytics_are_removable(): void
    {
        $this->freelancer();
        $f = $this->freelancer();
        \App\Models\PortfolioProject::forceCreate(['id' => (string) \Illuminate\Support\Str::ulid(), 'freelancer_profile_id' => $f->freelancerProfile->id, 'title' => 'W', 'status' => 'published', 'published_at' => now()]);
        \App\Support\DemoAnalytics::seed(5);
        $this->assertGreaterThan(0, DB::table('analytics_sessions')->where('is_demo', true)->count());
        foreach (['analytics_events', 'analytics_pageviews', 'analytics_sessions'] as $t) {
            DB::table($t)->where('is_demo', true)->delete();
        }
        $this->assertSame(0, DB::table('analytics_sessions')->count());
    }
}
