<?php

namespace Tests\Feature;

use App\Models\JobPost;
use App\Models\Project;
use App\Models\Review;
use App\Models\Skill;

class NavigationAndFiltersTest extends MarketplaceTestCase
{
    private function header(string $html): string
    {
        $start = strpos($html, '<header');
        $header = substr($html, $start, strpos($html, '</header>', $start) - $start);

        // drop the phone menu panel: it only shows on small screens, where the desktop links are hidden
        return preg_replace('~<nav id="app-nav-mobile".*?</nav>~s', '', $header);
    }

    public function test_no_sidebar_and_each_destination_appears_once_in_the_top_bar(): void
    {
        foreach ([$this->freelancer(), $this->client()] as $u) {
            $html = $this->actingAs($u)->get(route('dashboard'))->assertOk()->getContent();
            $this->assertStringNotContainsString('<aside', $this->header($html));
            $this->assertStringNotContainsString('<aside', substr($html, 0, strpos($html, '<main')));
            $nav = $this->header($html);
            foreach (['messages.index', 'notifications.index', 'settings', 'billing.index', 'profile', 'dashboard', 'how'] as $route) {
                $this->assertSame(1, substr_count($nav, 'href="'.route($route).'"'), "$route should appear exactly once in the top bar");
            }
        }
    }

    public function test_freelancer_and_client_get_their_own_links(): void
    {
        $f = $this->header($this->actingAs($this->freelancer())->get(route('dashboard'))->getContent());
        foreach (['Find Work', 'Portfolio', 'Proposals', 'Invitations', 'Saved jobs', 'Share Work'] as $label) $this->assertStringContainsString($label, $f);
        foreach (['Find Talent', 'Saved Talent', 'My Jobs', 'Post a Job'] as $label) $this->assertStringNotContainsString($label, $f);

        $c = $this->header($this->actingAs($this->client())->get(route('dashboard'))->getContent());
        foreach (['Find Talent', 'Saved Talent', 'My Jobs', 'Post a Job'] as $label) $this->assertStringContainsString($label, $c);
        foreach (['Find Work', 'Proposals', 'Invitations', 'Saved jobs', 'Share Work'] as $label) $this->assertStringNotContainsString($label, $c);
    }

    public function test_talent_filters(): void
    {
        $a = $this->freelancer(); $a->forceFill(['name' => 'Alpha Tester'])->save();
        $a->freelancerProfile->forceFill(['location' => 'Hawassa, Ethiopia', 'hourly_rate_minor' => 50000, 'languages' => ['Amharic', 'Afaan Oromo']])->save();
        $b = $this->freelancer(); $b->forceFill(['name' => 'Bravo Tester'])->save();
        $b->freelancerProfile->forceFill(['location' => 'Addis Ababa, Ethiopia', 'hourly_rate_minor' => 150000, 'languages' => ['English']])->save();
        $skill = Skill::create(['name' => 'Packaging', 'slug' => 'packaging']);
        $a->freelancerProfile->skills()->attach($skill);

        // a 5-star completed project for Alpha
        $client = $this->client();
        $app = $this->application($this->job($client), $a);
        $this->actingAs($client)->post(route('agreements.store', $app), $this->terms());
        $v = \App\Models\AgreementVersion::first();
        foreach ([$client, $a] as $u) $this->actingAs($u)->post(route('agreements.confirm', $v), ['content_hash' => $v->content_hash, 'acknowledge' => 1]);
        $p = Project::first(); $p->forceFill(['status' => 'completed'])->save();
        Review::create(['project_id' => $p->id, 'reviewer_id' => $client->id, 'reviewee_id' => $a->id, 'rating' => 5]);
        $this->app['auth']->forgetGuards();

        $only = fn (array $q, string $yes, string $no) => $this->get(route('freelancers.index', $q))->assertOk()->assertSee($yes)->assertDontSee($no);
        $only(['location' => 'Hawassa'], 'Alpha', 'Bravo');
        $only(['rating' => '4.5'], 'Alpha', 'Bravo');
        $only(['rate_max' => 1000], 'Alpha', 'Bravo');
        $only(['rate_min' => 1000], 'Bravo', 'Alpha');
        $only(['languages' => ['English']], 'Bravo', 'Alpha');
        $only(['skills' => ['packaging']], 'Alpha', 'Bravo');
        $only(['experienced' => 1], 'Alpha', 'Bravo');
        $this->get(route('freelancers.index', ['sort' => 'rate_high']))->assertOk()->assertSeeInOrder(['Bravo', 'Alpha']);
    }

    public function test_job_filters(): void
    {
        $c = $this->client();
        $c->clientProfile->forceFill(['is_verified' => true])->save();
        $old = $this->job($c); $old->forceFill(['title' => 'Old onsite poster', 'is_remote' => false, 'location' => 'Gondar', 'published_at' => now()->subDays(20)])->save();
        $other = $this->client();
        $new = $this->job($other); $new->forceFill(['title' => 'Fresh remote logo', 'is_remote' => true, 'location' => 'Addis Ababa', 'is_featured' => true])->save();

        $only = fn (array $q, string $yes, string $no) => $this->get(route('jobs.index', $q))->assertOk()->assertSee($yes)->assertDontSee($no);
        $only(['remote' => 1], 'Fresh remote logo', 'Old onsite poster');
        $only(['posted' => 7], 'Fresh remote logo', 'Old onsite poster');
        $only(['location' => 'Gondar'], 'Old onsite poster', 'Fresh remote logo');
        $only(['verified' => 1], 'Old onsite poster', 'Fresh remote logo');
        $only(['featured' => 1], 'Fresh remote logo', 'Old onsite poster');
    }
}
