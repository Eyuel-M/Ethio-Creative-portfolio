<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PortfolioBlock extends Model
{
    /** Block types the case-study builder offers. */
    public const TYPES = ['heading', 'text', 'image', 'gallery', 'image_text', 'quote', 'embed', 'spacer'];

    /** Allowed values per setting; the first value is the default. Anything else is dropped. */
    public const SETTINGS = [
        'width' => ['wide', 'full', 'narrow'],
        'align' => ['left', 'center'],
        'bg' => ['none', 'light', 'dark', 'accent'],
        'pad' => ['m', 'none', 's', 'l'],
        'level' => ['h2', 'h3'],
        'size' => ['base', 'lg'],
        'cols' => ['2', '3', '4'],
        'gap' => ['s', 'none', 'm'],
        'side' => ['left', 'right'],
        'space' => ['m', 's', 'l'],
        'line' => ['0', '1'],
    ];

    /** Which settings each type uses, and its own defaults where they differ from the global ones. */
    public const TYPE_SETTINGS = [
        'heading' => ['level' => 'h2', 'align' => 'left', 'width' => 'narrow', 'bg' => 'none', 'pad' => 's'],
        'text' => ['size' => 'base', 'align' => 'left', 'width' => 'narrow', 'bg' => 'none', 'pad' => 'm'],
        'image' => ['width' => 'full', 'bg' => 'none', 'pad' => 'none'],
        'gallery' => ['cols' => '2', 'gap' => 's', 'width' => 'full', 'bg' => 'none', 'pad' => 'none'],
        'image_text' => ['side' => 'left', 'width' => 'wide', 'bg' => 'light', 'pad' => 'l'],
        'quote' => ['align' => 'center', 'width' => 'narrow', 'bg' => 'none', 'pad' => 'l'],
        'embed' => ['width' => 'wide', 'bg' => 'none', 'pad' => 'm'],
        'spacer' => ['space' => 'm', 'line' => '0'],
    ];

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['settings' => 'array', 'media' => 'array'];
    }

    public static function cleanSettings(string $type, array $input): array
    {
        $out = [];
        foreach (self::TYPE_SETTINGS[$type] ?? [] as $key => $default) {
            $v = isset($input[$key]) ? (string) $input[$key] : $default;
            $out[$key] = in_array($v, self::SETTINGS[$key], true) ? $v : $default;
        }

        return $out;
    }

    /** Settings merged over the type's defaults. */
    public function opt(string $key): ?string
    {
        return ($this->settings ?? [])[$key] ?? (self::TYPE_SETTINGS[$this->type][$key] ?? null);
    }

    public function mediaUrl(): ?string
    {
        return $this->media_path ? \App\Support\Media::url($this->media_path) : null;
    }

    /** Gallery items as [url, alt]. */
    public function galleryItems(): array
    {
        return collect($this->media ?? [])->filter(fn ($m) => ! empty($m['path']))
            ->map(fn ($m) => ['url' => \App\Support\Media::url($m['path']), 'alt' => $m['alt'] ?? ''])->values()->all();
    }

    /** Only allowlisted video providers are embedded; arbitrary iframe HTML is never accepted. */
    public function embedSrc(): ?string
    {
        $url = (string) $this->embed_url;
        if (preg_match('~(?:youtube\.com/watch\?v=|youtu\.be/)([\w-]{11})~', $url, $m)) {
            return 'https://www.youtube-nocookie.com/embed/'.$m[1];
        }
        if (preg_match('~vimeo\.com/(\d+)~', $url, $m)) {
            return 'https://player.vimeo.com/video/'.$m[1];
        }

        return null;
    }
}
