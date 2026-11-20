<?php

namespace Tests\Feature;

use App\Models\PortfolioProject;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class PortfolioBuilderTest extends MarketplaceTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    public function test_every_block_type_saves_with_cleaned_settings_and_renders(): void
    {
        $f = $this->freelancer();
        $img = fn ($n) => UploadedFile::fake()->image($n, 800, 600);

        $this->actingAs($f)->post(route('portfolio.store'), [
            'title' => 'Builder project',
            'cover' => $img('cover.jpg'),
            'publish' => 1,
            'blocks' => [
                10 => ['type' => 'heading', 'body' => 'Big idea', 'settings' => json_encode(['level' => 'h3', 'align' => 'center', 'evil' => 'x'])],
                11 => ['type' => 'text', 'body' => 'Some words', 'settings' => json_encode(['size' => 'huge'])],
                12 => ['type' => 'image', 'image' => $img('a.jpg'), 'alt' => 'Alt A', 'caption' => 'Cap A', 'settings' => json_encode(['width' => 'narrow'])],
                13 => ['type' => 'gallery', 'images' => [$img('g1.jpg'), $img('g2.jpg'), $img('g3.jpg')], 'image_alts' => ['First alt', '', 'Third alt'], 'settings' => json_encode(['cols' => '3', 'gap' => 'none'])],
                14 => ['type' => 'image_text', 'image' => $img('b.jpg'), 'body' => "Title\nBody", 'settings' => json_encode(['side' => 'right', 'bg' => 'dark'])],
                15 => ['type' => 'quote', 'body' => 'Great work', 'caption' => 'A client'],
                16 => ['type' => 'embed', 'embed_url' => 'https://youtu.be/dQw4w9WgXcQ'],
                17 => ['type' => 'spacer', 'settings' => json_encode(['line' => '1'])],
                18 => ['type' => 'text', 'body' => ''], // empty blocks are dropped
            ],
        ])->assertRedirect();

        $p = PortfolioProject::where('title', 'Builder project')->firstOrFail();
        $blocks = $p->blocks()->orderBy('sort_order')->get();
        $this->assertSame(['heading', 'text', 'image', 'gallery', 'image_text', 'quote', 'embed', 'spacer'], $blocks->pluck('type')->all());
        $this->assertSame(['level' => 'h3', 'align' => 'center', 'width' => 'narrow', 'bg' => 'none', 'pad' => 's'], $blocks[0]->settings);
        $this->assertSame('base', $blocks[1]->settings['size']); // invalid value falls back to default
        $this->assertCount(3, $blocks[3]->media);
        $this->assertSame('3', $blocks[3]->settings['cols']);
        $this->assertSame(['First alt', null, 'Third alt'], array_column($blocks[3]->media, 'alt'));
        $this->assertTrue($p->isPublished());

        $this->get(route('portfolio.show', $p))->assertOk()
            ->assertSee('Big idea')->assertSee('Great work')->assertSee('youtube-nocookie.com/embed/dQw4w9WgXcQ', false)->assertSee('Alt A')->assertSee('alt="Third alt"', false);

        // Editing: keep one gallery image, drop the rest — removed files are cleaned up.
        $keep = $blocks[3]->media[0]['path'];
        $gone = $blocks[3]->media[1]['path'];
        $this->actingAs($f)->put(route('portfolio.update', $p), [
            'title' => 'Builder project',
            'blocks' => [1 => ['type' => 'gallery', 'gallery' => [['path' => $keep, 'alt' => 'Edited alt']], 'settings' => '{}']],
        ])->assertRedirect();
        $this->assertSame([['path' => $keep, 'alt' => 'Edited alt']], $p->blocks()->first()->media);
        Storage::disk('public')->assertExists($keep);
        Storage::disk('public')->assertMissing($gone);
    }

    public function test_cannot_reference_files_from_someone_elses_project(): void
    {
        $f = $this->freelancer();
        $this->actingAs($f)->post(route('portfolio.store'), [
            'title' => 'Sneaky',
            'blocks' => [1 => ['type' => 'gallery', 'gallery' => [['path' => 'portfolio/someone-else.jpg']]]],
        ])->assertSessionHasErrors('blocks.1.gallery.0.path');
    }

    public function test_builder_page_loads(): void
    {
        $f = $this->freelancer();
        $this->actingAs($f)->get(route('portfolio.create'))->assertOk()->assertSee('portfolioBuilder', false)->assertSee('Image + text');
    }
}
