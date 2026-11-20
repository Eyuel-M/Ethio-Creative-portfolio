<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;

/**
 * Makes uploaded images lighter WITHOUT visibly changing them. Rules, in order of priority:
 *
 *  1. Never lose what the image looks like.
 *     - PNGs (screenshots, illustrations, text) are only ever re-saved LOSSLESSLY.
 *     - Photos are re-encoded only as high-quality JPEG (q95 — measured ≥ 42 dB PSNR, visually identical), and only
 *       when that saves a lot. Lossy WebP is never used: GD's encoder halves colour detail and measurably softens
 *       sharp coloured edges at every quality setting.
 *     - Colour profiles (Display P3 / Adobe RGB from phones and design tools) are kept, so colours don't shift.
 *     - Images are only scaled down when wider than MAX_WIDTH (4K); tall pages are never shrunk.
 *  2. Protect privacy: GPS location, camera serials and other metadata are removed from every JPEG — losslessly,
 *     without touching the pixels — while the rotation flag is kept so photos still display upright.
 *  3. Keep the original: Media keeps the untouched upload in private storage, so nothing is ever unrecoverable.
 *
 * GIFs are never touched (animations survive).
 */
class ImageCompressor
{
    /** Wider images are scaled to this width (4K). Height is never limited, so long case-study pages stay sharp. */
    public const MAX_WIDTH = 3840;

    /** Visually lossless JPEG quality (see ImageQualityTest). */
    public const JPEG_QUALITY = 95;

    /** A photo is re-encoded (rather than kept byte-for-byte) only if that saves at least this much. */
    public const MIN_PHOTO_SAVING = 0.30;

    /** A lossless re-save is used only if it saves at least this much. */
    public const MIN_LOSSLESS_SAVING = 0.10;

    /**
     * @return array{data: string, ext: string, width: int, height: int, method: string}|null null = store the original untouched
     */
    public static function compress(UploadedFile $file): ?array
    {
        $mime = $file->getMimeType();
        if (! function_exists('imagecreatefromstring')) {
            return null;
        }

        try {
            $raw = file_get_contents($file->getRealPath());

            return match ($mime) {
                'image/jpeg' => self::jpeg($raw),
                'image/png' => self::png($raw),
                'image/webp' => self::webp($raw),
                default => null, // GIF and anything else: untouched
            };
        } catch (\Throwable $e) {
            report($e);

            return null;
        }
    }

    // ───────────────────────── JPEG (photos) ─────────────────────────

    private static function jpeg(string $raw): ?array
    {
        $segments = JpegMeta::parse($raw);
        if (! $segments) {
            return null;
        }
        $orientation = JpegMeta::orientation($segments);
        $icc = JpegMeta::iccSegments($segments);
        [$w, $h] = self::size($raw);
        $mustResize = $w > self::MAX_WIDTH;

        // Lossless baseline: same pixels, metadata removed (rotation flag + colour profile kept).
        $clean = JpegMeta::strip($segments, $orientation);
        $best = ['data' => $clean, 'ext' => 'jpg', 'width' => $w, 'height' => $h, 'method' => 'metadata-stripped'];

        if (! $mustResize && strlen($clean) < 300 * 1024) {
            return $best; // small already: never re-encode
        }

        self::raiseMemoryLimit();
        $img = @imagecreatefromstring($raw);
        if (! $img) {
            return $best;
        }
        $img = self::orient($img, $orientation);
        if ($mustResize) {
            $img = self::fitWidth($img);
        }
        $nw = imagesx($img);
        $nh = imagesy($img);

        // High-quality JPEG; the colour profile (if any) is re-attached so colours don't shift.
        $data = self::capture(fn () => imagejpeg($img, null, self::JPEG_QUALITY), function () use ($img) {
            imageinterlace($img, true);
        });
        $data = $data ? JpegMeta::insertAfterSoi($data, $icc) : null;
        $ext = 'jpg';
        imagedestroy($img);

        if ($data && ($mustResize || strlen($data) <= strlen($clean) * (1 - self::MIN_PHOTO_SAVING))) {
            return ['data' => $data, 'ext' => $ext, 'width' => $nw, 'height' => $nh, 'method' => $mustResize ? 'resized' : 'reencoded'];
        }

        return $best;
    }

    // ───────────────────────── PNG (screenshots, illustrations) ─────────────────────────

    private static function png(string $raw): ?array
    {
        [$w, $h] = self::size($raw);
        $hasProfile = str_contains(substr($raw, 0, 4096), 'iCCP');
        $mustResize = $w > self::MAX_WIDTH;
        if ($hasProfile && ! $mustResize) {
            return null; // keep colour-managed PNGs exactly as they are
        }

        self::raiseMemoryLimit();
        $img = @imagecreatefromstring($raw);
        if (! $img) {
            return null;
        }
        if ($mustResize) {
            $img = self::fitWidth($img);
        }
        imagealphablending($img, false);
        imagesavealpha($img, true);

        // Lossless only: lossless WebP if available, otherwise maximum-compression PNG.
        if (function_exists('imagewebp') && defined('IMG_WEBP_LOSSLESS') && ! $hasProfile) {
            $data = self::capture(fn () => imagewebp($img, null, IMG_WEBP_LOSSLESS));
            $ext = 'webp';
        } else {
            $data = self::capture(fn () => imagepng($img, null, 9));
            $ext = 'png';
        }
        $nw = imagesx($img);
        $nh = imagesy($img);
        imagedestroy($img);

        if ($data && ($mustResize || strlen($data) <= strlen($raw) * (1 - self::MIN_LOSSLESS_SAVING))) {
            return ['data' => $data, 'ext' => $ext, 'width' => $nw, 'height' => $nh, 'method' => $mustResize ? 'resized' : 'lossless'];
        }

        return null;
    }

    // ───────────────────────── WebP ─────────────────────────

    private static function webp(string $raw): ?array
    {
        [$w] = self::size($raw);
        if ($w <= self::MAX_WIDTH || ! function_exists('imagewebp')) {
            return null; // already efficient: keep it
        }
        self::raiseMemoryLimit();
        $img = @imagecreatefromstring($raw);
        if (! $img) {
            return null;
        }
        $img = self::fitWidth($img);
        imagealphablending($img, false);
        imagesavealpha($img, true);
        $data = defined('IMG_WEBP_LOSSLESS') ? self::capture(fn () => imagewebp($img, null, IMG_WEBP_LOSSLESS)) : null;
        $out = ['data' => $data, 'ext' => 'webp', 'width' => imagesx($img), 'height' => imagesy($img), 'method' => 'resized'];
        imagedestroy($img);

        return $data ? $out : null;
    }

    // ───────────────────────── helpers ─────────────────────────

    /** @return array{0: int, 1: int} */
    private static function size(string $raw): array
    {
        $info = @getimagesizefromstring($raw);

        return $info ? [(int) $info[0], (int) $info[1]] : [0, 0];
    }

    private static function orient(\GdImage $img, int $orientation): \GdImage
    {
        if (in_array($orientation, [2, 4, 5, 7], true)) {
            imageflip($img, IMG_FLIP_HORIZONTAL);
        }
        $angle = [3 => 180, 4 => 180, 5 => 270, 6 => 270, 7 => 90, 8 => 90][$orientation] ?? 0;
        if ($angle) {
            $rotated = imagerotate($img, $angle, 0);
            if ($rotated) {
                imagedestroy($img);

                return $rotated;
            }
        }

        return $img;
    }

    private static function fitWidth(\GdImage $img): \GdImage
    {
        $w = imagesx($img);
        $h = imagesy($img);
        if ($w <= self::MAX_WIDTH) {
            return $img;
        }
        $nh = max(1, (int) round($h * self::MAX_WIDTH / $w));
        $out = imagecreatetruecolor(self::MAX_WIDTH, $nh);
        imagealphablending($out, false);
        imagesavealpha($out, true);
        imagecopyresampled($out, $img, 0, 0, 0, 0, self::MAX_WIDTH, $nh, $w, $h);
        imagedestroy($img);

        return $out;
    }

    private static function capture(callable $write, ?callable $before = null): ?string
    {
        if ($before) {
            $before();
        }
        ob_start();
        try {
            $write();

            return ob_get_contents() ?: null;
        } finally {
            ob_end_clean();
        }
    }

    /** Decoding a large photo needs a few hundred MB; make sure we have room. */
    private static function raiseMemoryLimit(): void
    {
        $limit = ini_get('memory_limit');
        if ($limit === '-1') {
            return;
        }
        $bytes = (int) $limit * match (strtolower(substr($limit, -1))) { 'g' => 1 << 30, 'm' => 1 << 20, 'k' => 1 << 10, default => 1 };
        if ($bytes < 512 << 20) {
            @ini_set('memory_limit', '512M');
        }
    }
}
