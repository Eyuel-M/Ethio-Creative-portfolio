<?php

namespace Tests\Feature;

use App\Support\ImageCompressor;
use App\Support\JpegMeta;
use App\Support\Media;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/** The compressor must never visibly damage an image. These tests measure it. */
class ImageQualityTest extends MarketplaceTestCase
{
    private function gd(int $w, int $h, bool $smooth = false): \GdImage
    {
        $img = imagecreatetruecolor($w, $h);
        if ($smooth) { // photo-like gradients
            for ($y = 0; $y < $h; $y++) {
                for ($x = 0; $x < $w; $x += 4) {
                    $c = imagecolorallocate($img, (int) (128 + 127 * sin($x / 37)), (int) (128 + 127 * cos($y / 23)), (int) (($x + $y) % 256));
                    imagefilledrectangle($img, $x, $y, $x + 3, $y, $c);
                }
            }
        } else {
            for ($i = 0; $i < 600; $i++) {
                imagefilledellipse($img, mt_rand(0, $w), mt_rand(0, $h), mt_rand(5, 120), mt_rand(5, 120), mt_rand(0, 0xFFFFFF));
            }
        }

        return $img;
    }

    private function file(string $bytes, string $name, string $mime): UploadedFile
    {
        $tmp = tempnam(sys_get_temp_dir(), 'q');
        file_put_contents($tmp, $bytes);

        return new UploadedFile($tmp, $name, $mime, null, true);
    }

    private function jpegBytes(\GdImage $img, int $q = 100): string
    {
        ob_start();
        imagejpeg($img, null, $q);

        return ob_get_clean();
    }

    /** Peak signal-to-noise ratio between two same-size images (higher = closer; ≥ 40 dB is visually identical). */
    private function psnr(\GdImage $a, \GdImage $b): float
    {
        $w = imagesx($a);
        $h = imagesy($a);
        $sum = 0;
        $n = 0;
        for ($y = 0; $y < $h; $y += 3) {
            for ($x = 0; $x < $w; $x += 3) {
                $p = imagecolorat($a, $x, $y);
                $q = imagecolorat($b, $x, $y);
                foreach ([16, 8, 0] as $s) {
                    $d = (($p >> $s) & 255) - (($q >> $s) & 255);
                    $sum += $d * $d;
                    $n++;
                }
            }
        }
        $mse = $sum / $n;

        return $mse == 0 ? 99.0 : 10 * log10(255 * 255 / $mse);
    }

    public function test_photos_stay_visually_identical(): void
    {
        foreach ([false, true] as $smooth) {
            $src = $this->gd(1600, 1200, $smooth);
            $raw = $this->jpegBytes($src, 100);
            $out = ImageCompressor::compress($this->file($raw, 'p.jpg', 'image/jpeg'));
            $this->assertNotNull($out);
            $before = imagecreatefromstring($raw);
            $after = imagecreatefromstring($out['data']);
            $this->assertSame([1600, 1200], [imagesx($after), imagesy($after)], 'not resized');
            $this->assertGreaterThanOrEqual(40, $this->psnr($before, $after), "photo quality ({$out['method']})");
        }
    }

    public function test_screenshots_and_illustrations_are_pixel_identical(): void
    {
        $src = $this->gd(1200, 800);
        imagestring($src, 5, 20, 20, 'Sharp UI text 123', imagecolorallocate($src, 255, 255, 255));
        ob_start();
        imagepng($src, null, 0); // uncompressed PNG
        $raw = ob_get_clean();

        $out = ImageCompressor::compress($this->file($raw, 's.png', 'image/png'));
        $this->assertNotNull($out);
        $this->assertSame('lossless', $out['method']);
        $this->assertSame(99.0, $this->psnr(imagecreatefromstring($raw), imagecreatefromstring($out['data'])), 'every pixel identical');
    }

    public function test_long_pages_are_never_shrunk_and_only_very_wide_images_are(): void
    {
        $tall = ImageCompressor::compress($this->file($this->jpegBytes($this->gd(1440, 7000)), 't.jpg', 'image/jpeg'));
        $this->assertSame([1440, 7000], [$tall['width'], $tall['height']]);

        $wide = ImageCompressor::compress($this->file($this->jpegBytes($this->gd(6000, 2000)), 'w.jpg', 'image/jpeg'));
        $this->assertSame([ImageCompressor::MAX_WIDTH, 1280], [$wide['width'], $wide['height']]);
    }

    public function test_metadata_is_removed_without_touching_pixels_and_rotation_is_kept(): void
    {
        $plain = $this->jpegBytes($this->gd(400, 300), 90);
        // Inject an EXIF block with orientation 6 plus a fake "GPS" marker, and a colour profile.
        $exif = (new \ReflectionMethod(JpegMeta::class, 'orientationExif'))->invoke(null, 6);
        $gps = "\xFF\xE1".pack('n', 2 + 18)."Exif\0\0GPS-LAT-9.03";
        $icc = "\xFF\xE2".pack('n', 2 + 16)."ICC_PROFILE\0\x01\x01PQ";
        $raw = "\xFF\xD8".$exif.$gps.$icc.substr($plain, 2);

        $out = ImageCompressor::compress($this->file($raw, 'm.jpg', 'image/jpeg'));
        $this->assertSame('metadata-stripped', $out['method']);
        $this->assertStringNotContainsString('GPS-LAT', $out['data']);
        $this->assertStringContainsString("ICC_PROFILE\0", $out['data'], 'colour profile kept');
        $this->assertSame(6, JpegMeta::orientation(JpegMeta::parse($out['data'])), 'rotation kept');
        $this->assertSame(JpegMeta::parse($raw)['image'], JpegMeta::parse($out['data'])['image'], 'compressed image data untouched');
    }

    public function test_colour_profiles_survive_re_encoding(): void
    {
        $plain = $this->jpegBytes($this->gd(5000, 1000), 100);
        $icc = "\xFF\xE2".pack('n', 2 + 16)."ICC_PROFILE\0\x01\x01PQ";
        $out = ImageCompressor::compress($this->file("\xFF\xD8".$icc.substr($plain, 2), 'c.jpg', 'image/jpeg'));

        $this->assertSame('resized', $out['method']);
        $this->assertSame('jpg', $out['ext'], 'stays JPEG so the profile can travel with it');
        $this->assertStringContainsString("ICC_PROFILE\0\x01\x01PQ", $out['data']);
    }

    public function test_originals_are_kept_privately(): void
    {
        Storage::fake('public');
        Storage::fake('local');
        $raw = $this->jpegBytes($this->gd(5000, 1000), 100);
        $stored = Media::storeWithInfo($this->file($raw, 'o.jpg', 'image/jpeg'), 'portfolio');

        Storage::disk('local')->assertExists('originals/'.$stored['path']);
        $this->assertSame($raw, Storage::disk('local')->get('originals/'.$stored['path']));

        Media::delete($stored['path']);
        Storage::disk('local')->assertMissing('originals/'.$stored['path']);
    }
}
