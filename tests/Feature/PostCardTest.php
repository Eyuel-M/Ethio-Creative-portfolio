<?php

namespace Tests\Feature;

use App\Models\PortfolioProject;
use Illuminate\Support\Str;

class PostCardTest extends MarketplaceTestCase
{
    public function test_post_card_studio_uses_the_freelancers_published_work(): void
    {
        $f = $this->freelancer();
        $this->actingAs($f)->get(route('portfolio.post-card'))->assertRedirect(route('portfolio.index'));

        $p = PortfolioProject::forceCreate(['id' => (string) Str::ulid(), 'freelancer_profile_id' => $f->freelancerProfile->id, 'title' => 'Coffee Identity', 'status' => 'published', 'published_at' => now(), 'cover_path' => 'portfolio/c.jpg']);
        $r = $this->actingAs($f)->get(route('portfolio.post-card', ['project' => $p->id]));
        $r->assertOk()
            ->assertSee('shareCard(', false)->assertSee('New project: Coffee Identity')->assertSee('c.jpg', false)
            ->assertSee($f->freelancerProfile->slug, false);
        $this->actingAs($this->client())->get(route('portfolio.post-card'))->assertRedirect(); // freelancer-only page
    }

    public function test_publishing_prompts_to_announce(): void
    {
        $f = $this->freelancer();
        $p = PortfolioProject::forceCreate(['id' => (string) Str::ulid(), 'freelancer_profile_id' => $f->freelancerProfile->id, 'title' => 'Fresh work', 'status' => 'draft', 'cover_path' => 'portfolio/c.jpg']);
        $this->actingAs($f)->post(route('portfolio.publish', $p))->assertRedirect(route('portfolio.index'));
        $this->actingAs($f)->get(route('portfolio.index'))->assertSee('“Fresh work” is live')->assertSee(route('portfolio.post-card', ['project' => $p->id]), false);
    }
}
