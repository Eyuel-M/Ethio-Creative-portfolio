<?php

namespace Tests\Feature;

use App\Models\PortfolioProject;
use Illuminate\Support\Str;

class PublicShowcaseTest extends MarketplaceTestCase
{
    private function published($owner, string $title, int $views = 10): PortfolioProject
    {
        return PortfolioProject::forceCreate([
            'id' => (string) Str::ulid(), 'freelancer_profile_id' => $owner->freelancerProfile->id, 'title' => $title,
            'status' => 'published', 'published_at' => now(), 'views' => $views, 'cover_path' => 'https://example.test/'.Str::slug($title).'.jpg',
        ]);
    }

    private function namedFreelancer(): \App\Models\User
    {
        $f = $this->freelancer();
        $f->forceFill(['name' => 'Selamawit Gebremedhin'])->save();

        return $f->fresh();
    }

    public function test_visitors_see_first_names_and_members_see_full_names(): void
    {
        $f = $this->namedFreelancer();
        $this->published($f, 'Coffee label system');
        $profileUrl = route('freelancers.show', $f->freelancerProfile);

        foreach ([$profileUrl, route('freelancers.index')] as $url) {
            $this->get($url)->assertOk()->assertSee('Selamawit')->assertDontSee('Gebremedhin');
        }
        $this->actingAs($this->client())->get($profileUrl)->assertSee('Selamawit Gebremedhin');
    }

    public function test_home_shows_project_bento_with_explore_more(): void
    {
        $f = $this->freelancer();
        foreach (range(1, 7) as $i) {
            $this->published($f, "Showcase piece {$i}", $i * 10);
        }
        $this->get('/')->assertOk()->assertSee('Fresh work from')->assertSee('Showcase piece 7')->assertSee(route('explore'));
    }

    public function test_explore_is_public_and_redirects_members_to_their_feed(): void
    {
        $f = $this->freelancer();
        $this->published($f, 'Open gallery piece');
        $this->get(route('explore'))->assertOk()->assertSee('Open gallery piece')->assertSee('For You');
        $this->actingAs($this->client())->get(route('explore', ['q' => 'gallery']))->assertRedirect(route('home', ['q' => 'gallery']));
    }

    public function test_project_and_profile_pages_offer_share_links(): void
    {
        $f = $this->freelancer();
        $p = $this->published($f, 'Shareable project');
        $projectUrl = route('portfolio.show', $p);
        $this->get($projectUrl)->assertOk()->assertSee('t.me/share/url?url='.rawurlencode($projectUrl), false)->assertSee('og:image', false);
        $profileUrl = route('freelancers.show', $f->freelancerProfile);
        $this->get($profileUrl)->assertOk()->assertSee('wa.me/?text=', false)->assertSee($profileUrl, false);
    }

    public function test_drafts_get_no_share_button(): void
    {
        $f = $this->freelancer();
        $draft = PortfolioProject::forceCreate(['id' => (string) Str::ulid(), 'freelancer_profile_id' => $f->freelancerProfile->id, 'title' => 'Draft piece', 'status' => 'draft']);
        $this->actingAs($f)->get(route('portfolio.show', $draft))->assertOk()->assertDontSee('t.me/share', false);
    }

    public function test_single_page_lists_render_no_empty_pagination_box(): void
    {
        $this->published($this->freelancer(), 'Only one');
        $this->get(route('freelancers.index'))->assertOk()->assertDontSee('aria-label="Pagination"', false);
    }
}
