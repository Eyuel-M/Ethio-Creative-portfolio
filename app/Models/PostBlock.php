<?php

namespace App\Models;

use App\Support\Media;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/** One block of an Insights post. Settings are allowlisted per type, like portfolio blocks. */
class PostBlock extends Model
{
    public const TYPES = ['heading', 'text', 'image', 'gallery', 'image_text', 'quote', 'embed', 'callout', 'list', 'stats', 'table', 'faq', 'button', 'download', 'spacer'];

    /** Allowed values per setting; the first value is the default. */
    public const SETTINGS = [
        'width' => ['narrow', 'wide', 'full'],
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
        'tone' => ['info', 'tip', 'warning', 'success', 'note'],
        'style' => ['bullet', 'number', 'check'],
        'header' => ['1', '0'],
        'striped' => ['1', '0'],
        'variant' => ['dark', 'accent', 'light'],
        'open' => ['0', '1'],
        'dropcap' => ['0', '1'],
    ];

    public const TYPE_SETTINGS = [
        'heading' => ['level' => 'h2', 'align' => 'left', 'width' => 'narrow', 'bg' => 'none', 'pad' => 's'],
        'text' => ['size' => 'base', 'align' => 'left', 'dropcap' => '0', 'width' => 'narrow', 'bg' => 'none', 'pad' => 's'],
        'image' => ['width' => 'wide', 'bg' => 'none', 'pad' => 'm'],
        'gallery' => ['cols' => '2', 'gap' => 's', 'width' => 'wide', 'bg' => 'none', 'pad' => 'm'],
        'image_text' => ['side' => 'left', 'width' => 'wide', 'bg' => 'light', 'pad' => 'l'],
        'quote' => ['align' => 'left', 'width' => 'narrow', 'bg' => 'none', 'pad' => 'm'],
        'embed' => ['width' => 'wide', 'bg' => 'none', 'pad' => 'm'],
        'callout' => ['tone' => 'info', 'width' => 'narrow', 'pad' => 's'],
        'list' => ['style' => 'bullet', 'width' => 'narrow', 'bg' => 'none', 'pad' => 's'],
        'stats' => ['cols' => '3', 'width' => 'wide', 'bg' => 'light', 'pad' => 'm'],
        'table' => ['header' => '1', 'striped' => '1', 'width' => 'narrow', 'pad' => 'm'],
        'faq' => ['open' => '0', 'width' => 'narrow', 'bg' => 'none', 'pad' => 'm'],
        'button' => ['variant' => 'dark', 'align' => 'center', 'width' => 'narrow', 'bg' => 'accent', 'pad' => 'l'],
        'download' => ['width' => 'narrow', 'pad' => 's'],
        'spacer' => ['space' => 'm', 'line' => '0'],
    ];

    /** Callout tones: [label, icon, classes]. */
    public const TONES = [
        'info' => ['Info', 'help', 'border-[#2a78d6]/25 bg-[#eaf2fc] text-[#1a4f8f]'],
        'tip' => ['Tip', 'sparkles', 'border-[#7c4ddc]/25 bg-[#f1ebfc] text-[#4d2a99]'],
        'warning' => ['Heads up', 'flag', 'border-[#d98b06]/30 bg-[#fdf3e1] text-[#7a4a00]'],
        'success' => ['Good to know', 'check-circle', 'border-[#16855a]/25 bg-[#e6f4ed] text-[#0e5a3c]'],
        'note' => ['Note', 'file', 'border-line bg-zinc-50 text-ink-soft'],
    ];

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['settings' => 'array', 'media' => 'array', 'items' => 'array'];
    }

    public function post()
    {
        return $this->belongsTo(Post::class);
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

    public function opt(string $key): ?string
    {
        return ($this->settings ?? [])[$key] ?? (self::TYPE_SETTINGS[$this->type][$key] ?? null);
    }

    public function mediaUrl(): ?string
    {
        return $this->media_path ? Media::url($this->media_path) : null;
    }

    public function galleryItems(): array
    {
        return collect($this->media ?? [])->filter(fn ($m) => ! empty($m['path']))
            ->map(fn ($m) => ['url' => Media::url($m['path']), 'alt' => $m['alt'] ?? ''])->values()->all();
    }

    /** Anchor for headings (used by the table of contents). */
    public function anchor(): string
    {
        return Str::slug(Str::limit((string) $this->body, 60, '')).'-'.$this->id;
    }

    /** Only allowlisted video providers are embedded. */
    public function embedSrc(): ?string
    {
        $url = (string) $this->url;
        if (preg_match('~(?:youtube\.com/watch\?v=|youtu\.be/)([\w-]{11})~', $url, $m)) {
            return 'https://www.youtube-nocookie.com/embed/'.$m[1];
        }
        if (preg_match('~vimeo\.com/(\d+)~', $url, $m)) {
            return 'https://player.vimeo.com/video/'.$m[1];
        }

        return null;
    }

    /** Table rows from "a | b | c" lines. */
    public function tableRows(): array
    {
        return collect(preg_split('/\R/', (string) $this->body))
            ->map(fn ($l) => trim($l))->filter(fn ($l) => $l !== '' && ! preg_match('/^\|?\s*:?-{2,}/', $l))
            ->map(fn ($l) => array_map('trim', explode('|', trim($l, '| '))))->values()->all();
    }

    /** List items, one per line (leading "-", "*", "1." markers are ignored). */
    public function listItems(): array
    {
        return collect(preg_split('/\R/', (string) $this->body))
            ->map(fn ($l) => trim(preg_replace('/^(\s*[-*•]|\s*\d+[.)])\s+/', '', $l)))->filter()->values()->all();
    }

    /** A link is safe to render when it is http(s), mailto or a site-relative path. */
    public static function safeUrl(?string $url): ?string
    {
        $url = trim((string) $url);

        return preg_match('~^(https?://|mailto:|/(?!/)|#)~i', $url) ? $url : null;
    }

    public function fileName(): ?string
    {
        return $this->media_path ? ($this->caption ?: basename($this->media_path)) : null;
    }
}
