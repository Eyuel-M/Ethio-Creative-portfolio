<?php

namespace Tests\Feature;

use App\Http\Controllers\FeedController;
use App\Models\JobPost;
use App\Models\PortfolioProject;
use App\Models\User;
use App\Support\DemoData;
use Database\Seeders\DemoSeeder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ExplorePreviewAndDemoDataTest extends MarketplaceTestCase
{
    private function projects(int $n, string $prefix = 'Piece'): void
    {
        $f = $this->freelancer();
        foreach (range(1, $n) as $i) {
            PortfolioProject::forceCreate(['id' => (string) Str::ulid(), 'freelancer_profile_id' => $f->freelancerProfile->id,
                'title' => "{$prefix} {$i}", 'status' => 'published', 'published_at' => now()->subMinutes($i)]);
        }
    }

    public function test_visitors_get_a_capped_preview_with_a_sign_up_wall(): void
    {
        $this->projects(30);
        $r = $this->get(route('explore'))->assertOk()
            ->assertSee('See all 30 projects')->assertSee('Join free')
            ->assertDontSee('Show results')      // no filter panel
            ->assertDontSee('Load more')
            ->assertDontSee('Most Loved');
        $this->assertCount(FeedController::PREVIEW, $r->viewData('projects')->getCollection());

        // Search, sort, filters and paging are ignored for visitors.
        $page2 = $this->get(route('explore', ['page' => 2, 'q' => 'Piece 30', 'sort' => 'newest', 'pro' => 1]))->viewData('projects');
        $this->assertSame(1, $page2->currentPage());
        $this->assertCount(FeedController::PREVIEW, $page2->getCollection());
    }

    public function test_members_keep_the_full_feed(): void
    {
        $this->projects(30);
        $this->actingAs($this->client())->get('/')->assertOk()
            ->assertSee('Show results')->assertSee('Load more')->assertSee('Most Loved')->assertDontSee('See all 30 projects');
        $this->actingAs($this->client())->get('/?q=Piece+7')->assertSee('Piece 7');
    }

    public function test_explore_is_not_in_the_visitor_top_bar(): void
    {
        $this->get(route('pricing'))->assertOk()->assertDontSee(route('explore'));
    }

    public function test_demo_data_loads_and_clears_without_touching_real_accounts(): void
    {
        Storage::fake('public');
        $real = $this->freelancer();
        PortfolioProject::forceCreate(['id' => (string) Str::ulid(), 'freelancer_profile_id' => $real->freelancerProfile->id, 'title' => 'Mine', 'status' => 'published', 'published_at' => now()]);
        $realClient = $this->client();
        $realJob = $this->job($realClient);

        $this->seed(DemoSeeder::class);
        $this->assertTrue(DemoData::present());
        $this->assertGreaterThan(10, PortfolioProject::count());
        $this->artisan('demo:seed')->expectsOutputToContain('already loaded')->assertExitCode(1);

        // a real user interacts with demo content
        PortfolioProject::whereHas('profile.user', fn ($q) => $q->where('email', 'like', '%@demo.test'))->first()->likes()->attach($realClient->id);

        $this->artisan('demo:clear', ['--force' => true])->assertExitCode(0);
        $this->assertFalse(DemoData::present());
        $this->assertSame(0, User::where('email', 'like', '%@demo.test')->count());
        $this->assertNotNull($real->fresh());
        $this->assertNotNull($realClient->fresh());
        $this->assertSame(['Mine'], PortfolioProject::pluck('title')->all());
        $this->assertSame([$realJob->id], JobPost::pluck('id')->all());
        Storage::disk('public')->assertMissing('seed');
    }

    public function test_demo_seed_repairs_half_loaded_placeholder_data(): void
    {
        Storage::fake('public');
        $real = $this->client();
        // what an earlier crashed run left behind: placeholder accounts, no work, no jobs
        foreach (['admin@demo.test', 'freelancer@demo.test'] as $email) {
            User::forceCreate(['name' => 'Leftover', 'email' => $email, 'password' => bcrypt('x')]);
        }
        $this->assertFalse(DemoData::complete());
        $this->assertStringContainsString('INCOMPLETE', DemoData::status()['State']);

        $this->artisan('demo:seed')->assertExitCode(0);

        $this->assertTrue(DemoData::complete());
        $this->assertSame('loaded', DemoData::status()['State']);
        $this->assertNotNull($real->fresh());
        $this->artisan('demo:status')->expectsOutputToContain('Placeholder live jobs')->assertExitCode(0);
    }
}
