<?php

namespace Tests\Feature;

use App\Models\PortfolioProject;
use Illuminate\Support\Str;

class DiscoveryFeedTest extends MarketplaceTestCase
{
    private function project($owner, string $status = 'published', string $title = 'Coffee bag redesign'): PortfolioProject
    {
        return PortfolioProject::forceCreate([
            'id' => (string) Str::ulid(), 'freelancer_profile_id' => $owner->freelancerProfile->id,
            'title' => $title, 'status' => $status, 'published_at' => now(),
        ]);
    }

    public function test_guests_see_the_landing_page(): void
    {
        $this->get('/')->assertOk()->assertSee('Search Talent')->assertDontSee('Most Loved');
    }

    public function test_signed_in_client_and_freelancer_see_the_feed(): void
    {
        $this->project($this->freelancer(), 'published', 'Published case study');
        $this->project($this->freelancer(), 'draft', 'Secret draft');

        foreach ([$this->client(), $this->freelancer()] as $user) {
            $this->actingAs($user)->get('/')->assertOk()
                ->assertSee('Most Loved')
                ->assertSee('Published case study')
                ->assertDontSee('Secret draft')
                ->assertDontSee('Search Talent');
        }
    }

    public function test_bento_projects_are_not_lost_or_duplicated(): void
    {
        $f = $this->freelancer();
        foreach (range(1, 7) as $i) {
            $this->project($f, 'published', "Work number {$i}")->forceFill(['views' => $i * 10])->save();
        }
        $html = $this->actingAs($this->client())->get('/')->assertOk()->getContent();
        foreach (range(1, 7) as $i) {
            $this->assertSame(1, substr_count($html, ">Work number {$i}<"), "Work number {$i} should appear exactly once");
        }
    }

    public function test_feed_filters_by_search(): void
    {
        $f = $this->freelancer();
        $this->project($f, 'published', 'Injera menu shoot');
        $this->project($f, 'published', 'Banking app UI');
        $this->actingAs($this->client())->get('/?q=injera')->assertSee('Injera menu shoot')->assertDontSee('Banking app UI');
    }

    public function test_like_toggles_and_rules_hold(): void
    {
        $owner = $this->freelancer();
        $p = $this->project($owner);
        $client = $this->client();

        $this->actingAs($client)->postJson(route('portfolio.like', $p))->assertOk()->assertJson(['liked' => true, 'count' => 1]);
        $this->actingAs($client)->postJson(route('portfolio.like', $p))->assertOk()->assertJson(['liked' => false, 'count' => 0]);

        $this->actingAs($owner)->postJson(route('portfolio.like', $p))->assertStatus(422);   // can't like own work
        $draft = $this->project($owner, 'draft');
        $this->actingAs($client)->postJson(route('portfolio.like', $draft))->assertNotFound(); // drafts are private
        $this->app['auth']->forgetGuards();
        $this->postJson(route('portfolio.like', $p))->assertUnauthorized();                     // guests can't like
    }

    public function test_sign_in_lands_on_discover(): void
    {
        $u = $this->client();
        $this->post('/login', ['email' => $u->email, 'password' => 'password'])->assertRedirect(route('home'));
    }

    public function test_sign_up_accepts_optional_photo_and_lands_on_discover_after_verifying(): void
    {
        \Illuminate\Support\Facades\Storage::fake('public');
        $this->post('/register', [
            'name' => 'Abeba Tadesse', 'email' => 'abeba@example.com', 'password' => 'secret123', 'password_confirmation' => 'secret123',
            'role' => 'client', 'terms' => 1, 'avatar' => \Illuminate\Http\UploadedFile::fake()->image('me.jpg', 300, 300),
        ])->assertRedirect(route('verification.notice'));
        $u = \App\Models\User::where('email', 'abeba@example.com')->firstOrFail();
        $this->assertNotNull($u->avatar_path);
        \Illuminate\Support\Facades\Storage::disk('public')->assertExists($u->avatar_path);

        $link = \Illuminate\Support\Facades\URL::temporarySignedRoute('verification.verify', now()->addHour(), ['id' => $u->id, 'hash' => sha1($u->email)]);
        $this->actingAs($u)->get($link)->assertRedirect(route('home'));
    }

    public function test_feed_filters_available_and_pro(): void
    {
        $busy = $this->freelancer();
        $busy->freelancerProfile->forceFill(['availability' => 'busy'])->save();
        $pro = $this->freelancer();
        $pro->freelancerProfile->forceFill(['plan' => 'professional'])->save();
        $this->project($busy, 'published', 'Busy person work');
        $this->project($pro, 'published', 'Pro person work');

        $c = $this->client();
        $this->actingAs($c)->get('/?available=1')->assertSee('Pro person work')->assertDontSee('Busy person work');
        $this->actingAs($c)->get('/?pro=1')->assertSee('Pro person work')->assertDontSee('Busy person work');
    }
}
