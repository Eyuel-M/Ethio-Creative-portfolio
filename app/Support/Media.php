<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Public portfolio media. Uses the configured media disk (local "public" in development;
 * an R2/S3 disk once the data-residency review approves it). Object keys are random.
 */
class Media
{
    public static function disk(): string
    {
        return config('marketplace.media_disk', 'public');
    }

    /** Stores an uploaded image, compressed by ImageCompressor (originals are kept when compression can't help). */
    public static function store(UploadedFile $file, string $folder): string
    {
        return self::storeWithInfo($file, $folder)['path'];
    }

    /** @return array{path: string, bytes: int, original_bytes: int} */
    public static function storeWithInfo(UploadedFile $file, string $folder): array
    {
        $original = (int) $file->getSize();
        $out = ImageCompressor::compress($file);
        if ($out) {
            $key = $folder.'/'.Str::random(40).'.'.$out['ext'];
            Storage::disk(self::disk())->put($key, $out['data']);

            return ['path' => $key, 'bytes' => strlen($out['data']), 'original_bytes' => $original];
        }
        $key = $folder.'/'.Str::random(40).'.'.strtolower($file->extension());
        Storage::disk(self::disk())->putFileAs(dirname($key), $file, basename($key));

        return ['path' => $key, 'bytes' => $original, 'original_bytes' => $original];
    }

    public static function url(?string $path): ?string
    {
        if (! $path) {
            return null;
        }
        if (Str::startsWith($path, ['http://', 'https://'])) {
            return $path;
        }

        return Storage::disk(self::disk())->url($path);
    }

    public static function delete(?string $path): void
    {
        if ($path && ! Str::startsWith($path, ['http://', 'https://'])) {
            Storage::disk(self::disk())->delete($path);
        }
    }
}
