<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;

/**
 * Shrinks uploaded photos before they are stored:
 *  - turns phone photos the right way up (EXIF orientation) and strips metadata such as GPS location,
 *  - scales anything larger than MAX_EDGE down,
 *  - re-encodes to WebP (keeps transparency) or, without WebP support, to progressive JPEG / PNG.
 *
 * The original is kept whenever compression can't help (GIFs keep their animation, or the result isn't
 * meaningfully smaller) or when the server's GD build can't read/write the format.
 */
class ImageCompressor
{
    public const MAX_EDGE = 2560;

    public const WEBP_QUALITY = 80;

    public const JPEG_QUALITY = 82;

    /** Keep the re-encoded file only if it is at least this much smaller (unless we had to resize/rotate/strip). */
    public const MIN_SAVING = 0.10;

    /**
     * @return array{data: string, ext: string, width: int, height: int}|null null = store the original untouched
     */
    public static function compress(UploadedFile $file): ?array
    {
        $mime = $file->getMimeType();
        if (! in_array($mime, ['image/jpeg', 'image/png', 'image/webp'], true) || ! function_exists('imagecreatefromstring')) {
            return null;
        }

        try {
            self::raiseMemoryLimit();
            $raw = file_get_contents($file->getRealPath());
            $img = @imagecreatefromstring($raw);
            if (! $img) {
                return null;
            }

            $mustReencode = false;

            // Phone photos: rotate to match EXIF orientation; any EXIF (GPS, camera serials) gets stripped by re-encoding.
            if ($mime === 'image/jpeg' && function_exists('exif_read_data')) {
                $exif = @exif_read_data($file->getRealPath());
                if (is_array($exif) && $exif) {
                    $mustReencode = true;
                    $angle = [3 => 180, 6 => -90, 8 => 90][$exif['Orientation'] ?? 1] ?? 0;
                    if ($angle) {
                        $rotated = imagerotate($img, $angle, 0);
                        if ($rotated) {
                            imagedestroy($img);
                            $img = $rotated;
                        }
                    }
                }
            }

            $w = imagesx($img);
            $h = imagesy($img);
            if (max($w, $h) > self::MAX_EDGE) {
                $scale = self::MAX_EDGE / max($w, $h);
                $nw = max(1, (int) round($w * $scale));
                $nh = max(1, (int) round($h * $scale));
                $resized = imagecreatetruecolor($nw, $nh);
                imagealphablending($resized, false);
                imagesavealpha($resized, true);
                imagecopyresampled($resized, $img, 0, 0, 0, 0, $nw, $nh, $w, $h);
                imagedestroy($img);
                $img = $resized;
                [$w, $h] = [$nw, $nh];
                $mustReencode = true;
            }

            [$data, $ext] = self::encode($img, $mime);
            imagedestroy($img);

            if ($data === null) {
                return null;
            }
            if (! $mustReencode && strlen($data) > strlen($raw) * (1 - self::MIN_SAVING)) {
                return null; // not worth it — keep the original bytes
            }

            return ['data' => $data, 'ext' => $ext, 'width' => $w, 'height' => $h];
        } catch (\Throwable $e) {
            report($e);

            return null;
        }
    }

    /** @return array{0: ?string, 1: string} */
    private static function encode(\GdImage $img, string $mime): array
    {
        ob_start();
        try {
            if (function_exists('imagewebp')) {
                imagealphablending($img, false);
                imagesavealpha($img, true);
                imagewebp($img, null, self::WEBP_QUALITY);

                return [ob_get_contents() ?: null, 'webp'];
            }
            if ($mime === 'image/png' && function_exists('imagepng')) {
                imagesavealpha($img, true);
                imagepng($img, null, 9);

                return [ob_get_contents() ?: null, 'png'];
            }
            if (function_exists('imagejpeg') && $mime !== 'image/png') {
                imageinterlace($img, true);
                imagejpeg($img, null, self::JPEG_QUALITY);

                return [ob_get_contents() ?: null, 'jpg'];
            }

            return [null, ''];
        } finally {
            ob_end_clean();
        }
    }

    /** Decoding an 8000×8000 photo needs ~256 MB; make sure we have room. */
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
