<?php

namespace Tests\Feature;

use App\Models\MediaUpload;
use App\Models\PortfolioProject;
use App\Support\ImageCompressor;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class ImageUploadTest extends MarketplaceTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    /** A noisy (hard to compress) JPEG of the given size, written by GD. */
    private function photo(int $w, int $h, string $name = 'photo.jpg'): UploadedFile
    {
        $img = imagecreatetruecolor($w, $h);
        for ($i = 0; $i < 4000; $i++) {
            imagefilledellipse($img, mt_rand(0, $w), mt_rand(0, $h), mt_rand(5, 80), mt_rand(5, 80), mt_rand(0, 0xFFFFFF));
        }
        $tmp = tempnam(sys_get_temp_dir(), 'img');
        imagejpeg($img, $tmp, 100);

        return new UploadedFile($tmp, $name, 'image/jpeg', null, true);
    }

    public function test_large_photos_are_resized_and_re_encoded(): void
    {
        $file = $this->photo(4000, 2000);
        $out = ImageCompressor::compress($file);

        $this->assertNotNull($out);
        $this->assertSame(ImageCompressor::MAX_EDGE, $out['width']);
        $this->assertSame(1280, $out['height']);
        $this->assertLessThan($file->getSize(), strlen($out['data']));
        $this->assertSame(function_exists('imagewebp') ? 'webp' : 'jpg', $out['ext']);
    }

    public function test_gifs_are_left_untouched(): void
    {
        $this->assertNull(ImageCompressor::compress(UploadedFile::fake()->image('anim.gif', 300, 300)));
    }

    public function test_upload_endpoint_compresses_and_parks_the_image(): void
    {
        $f = $this->freelancer();
        $res = $this->actingAs($f)->postJson(route('media.uploads.store'), ['image' => $this->photo(3000, 3000)])
            ->assertCreated()->assertJsonStructure(['path', 'url', 'bytes', 'original_bytes']);

        Storage::disk('public')->assertExists($res['path']);
        $this->assertLessThan($res['original_bytes'], $res['bytes']);
        $this->assertDatabaseHas('media_uploads', ['user_id' => $f->id, 'path' => $res['path']]);

        $this->actingAs($f)->postJson(route('media.uploads.store'), ['image' => UploadedFile::fake()->create('notes.pdf', 10, 'application/pdf')])
            ->assertUnprocessable();
    }

    public function test_saving_a_project_claims_uploads_and_rejects_other_peoples(): void
    {
        $f = $this->freelancer();
        $cover = $this->actingAs($f)->postJson(route('media.uploads.store'), ['image' => $this->photo(800, 600)])->json('path');
        $img = $this->actingAs($f)->postJson(route('media.uploads.store'), ['image' => $this->photo(800, 600)])->json('path');

        $this->actingAs($f)->post(route('portfolio.store'), [
            'title' => 'Uploaded ahead',
            'cover_upload' => $cover,
            'blocks' => [1 => ['type' => 'image', 'existing' => $img, 'alt' => 'A'], 2 => ['type' => 'gallery', 'gallery' => [['path' => $img, 'alt' => 'B']]]],
        ])->assertRedirect();

        $p = PortfolioProject::where('title', 'Uploaded ahead')->firstOrFail();
        $this->assertSame($cover, $p->cover_path);
        $this->assertSame($img, $p->blocks()->first()->media_path);
        $this->assertSame(0, MediaUpload::where('user_id', $f->id)->count()); // claimed

        // Someone else's pending upload can't be attached.
        $other = $this->freelancer();
        $theirs = $this->actingAs($other)->postJson(route('media.uploads.store'), ['image' => $this->photo(400, 300)])->json('path');
        $this->actingAs($f)->post(route('portfolio.store'), ['title' => 'Sneaky', 'cover_upload' => $theirs])->assertSessionHasErrors('cover_upload');
    }

    public function test_unused_uploads_are_pruned_after_a_day(): void
    {
        $f = $this->freelancer();
        $old = $this->actingAs($f)->postJson(route('media.uploads.store'), ['image' => $this->photo(400, 300)])->json('path');
        $new = $this->actingAs($f)->postJson(route('media.uploads.store'), ['image' => $this->photo(400, 300)])->json('path');
        MediaUpload::where('path', $old)->update(['created_at' => now()->subHours(MediaUpload::KEEP_HOURS + 1)]);

        $this->artisan('media:prune-uploads')->assertSuccessful();

        Storage::disk('public')->assertMissing($old);
        Storage::disk('public')->assertExists($new);
        $this->assertDatabaseMissing('media_uploads', ['path' => $old]);
    }
}
