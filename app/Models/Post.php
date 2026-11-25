<?php

namespace App\Models;

use App\Support\Media;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/** An Insights post: a study, guide, product update, spotlight or article, built from blocks. */
class Post extends Model
{
    use HasUlids;

    /** type => [label, plural, icon] */
    public const TYPES = [
        'article' => ['Article', 'Articles', 'pen'],
        'study' => ['Study', 'Studies & reports', 'chart'],
        'guide' => ['Guide', 'Guides', 'file'],
        'update' => ['Update', 'Product updates', 'megaphone'],
        'spotlight' => ['Spotlight', 'Spotlights', 'star'],
    ];

    public const AUDIENCES = ['everyone' => 'Everyone', 'freelancers' => 'For freelancers', 'clients' => 'For clients'];

    /** Page settings and their allowed values; the first value is the default. */
    public const SETTINGS = [
        'layout' => ['article', 'feature', 'minimal'],   // article: reading column + contents sidebar · feature: big hero · minimal: text only
        'cover' => ['hero', 'contained', 'none'],
        'accent' => ['blue', 'violet', 'green', 'amber', 'rose', 'ink'],
        'toc' => ['1', '0'],
        'reading_time' => ['1', '0'],
        'author_box' => ['1', '0'],
        'share' => ['1', '0'],
        'related' => ['1', '0'],
    ];

    /** Accent colour classes. Written out in full so Tailwind keeps them. */
    public const ACCENTS = [
        'blue' => ['text' => 'text-[#2a78d6]', 'bg' => 'bg-[#2a78d6]', 'soft' => 'bg-[#eaf2fc]', 'ring' => 'ring-[#2a78d6]', 'border' => 'border-[#2a78d6]', 'grad' => 'from-[#dbe9fb] via-[#eef4fd] to-white', 'hex' => '#2a78d6'],
        'violet' => ['text' => 'text-[#7c4ddc]', 'bg' => 'bg-[#7c4ddc]', 'soft' => 'bg-[#f1ebfc]', 'ring' => 'ring-[#7c4ddc]', 'border' => 'border-[#7c4ddc]', 'grad' => 'from-[#e9defb] via-[#f4effd] to-white', 'hex' => '#7c4ddc'],
        'green' => ['text' => 'text-[#16855a]', 'bg' => 'bg-[#16855a]', 'soft' => 'bg-[#e6f4ed]', 'ring' => 'ring-[#16855a]', 'border' => 'border-[#16855a]', 'grad' => 'from-[#d6efe3] via-[#ebf7f1] to-white', 'hex' => '#16855a'],
        'amber' => ['text' => 'text-[#b26a00]', 'bg' => 'bg-[#d98b06]', 'soft' => 'bg-[#fdf3e1]', 'ring' => 'ring-[#d98b06]', 'border' => 'border-[#d98b06]', 'grad' => 'from-[#fbe8c4] via-[#fdf4e3] to-white', 'hex' => '#d98b06'],
        'rose' => ['text' => 'text-[#c8325a]', 'bg' => 'bg-[#c8325a]', 'soft' => 'bg-[#fcebf0]', 'ring' => 'ring-[#c8325a]', 'border' => 'border-[#c8325a]', 'grad' => 'from-[#f9dbe4] via-[#fcedf2] to-white', 'hex' => '#c8325a'],
        'ink' => ['text' => 'text-ink', 'bg' => 'bg-ink', 'soft' => 'bg-zinc-100', 'ring' => 'ring-ink', 'border' => 'border-ink', 'grad' => 'from-zinc-200 via-zinc-100 to-white', 'hex' => '#0c0c10'],
    ];

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['topics' => 'array', 'settings' => 'array', 'published_at' => 'datetime', 'featured_at' => 'datetime'];
    }

    public function blocks()
    {
        return $this->hasMany(PostBlock::class)->orderBy('sort_order');
    }

    public function author()
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    /** Visible to the public: published, and its publish time has arrived. */
    public function scopePublic(Builder $q): Builder
    {
        return $q->where('status', 'published')->whereNotNull('published_at')->where('published_at', '<=', now());
    }

    public function isPublic(): bool
    {
        return $this->status === 'published' && $this->published_at && $this->published_at->lte(now());
    }

    public function isScheduled(): bool
    {
        return $this->status === 'published' && $this->published_at && $this->published_at->isFuture();
    }

    /** draft | scheduled | published | archived — for badges. */
    public function state(): string
    {
        return $this->isScheduled() ? 'scheduled' : $this->status;
    }

    public static function cleanSettings(array $input): array
    {
        $out = [];
        foreach (self::SETTINGS as $key => $allowed) {
            $v = isset($input[$key]) ? (string) $input[$key] : $allowed[0];
            $out[$key] = in_array($v, $allowed, true) ? $v : $allowed[0];
        }

        return $out;
    }

    public function opt(string $key): string
    {
        $v = ($this->settings ?? [])[$key] ?? null;

        return in_array($v, self::SETTINGS[$key] ?? [], true) ? $v : self::SETTINGS[$key][0];
    }

    public function shows(string $key): bool
    {
        return $this->opt($key) === '1';
    }

    public function accent(string $part = 'text'): string
    {
        return self::ACCENTS[$this->opt('accent')][$part];
    }

    public function typeLabel(): string
    {
        return self::TYPES[$this->type][0] ?? 'Article';
    }

    public function typeIcon(): string
    {
        return self::TYPES[$this->type][2] ?? 'pen';
    }

    public function coverUrl(): ?string
    {
        return $this->cover_path ? Media::url($this->cover_path) : null;
    }

    public function byline(): string
    {
        return $this->author_label ?: ($this->author?->name ?? config('marketplace.brand').' team');
    }

    public function metaTitle(): string
    {
        return $this->seo_title ?: $this->title;
    }

    public function metaDescription(): string
    {
        return $this->seo_description ?: ($this->excerpt ?: Str::limit(strip_tags((string) $this->blocks->firstWhere('type', 'text')?->body), 170));
    }

    /** h2/h3 headings for the table of contents, with the anchor each block renders. */
    public function outline(): array
    {
        return $this->blocks->where('type', 'heading')->filter(fn ($b) => filled($b->body))
            ->map(fn ($b) => ['id' => $b->anchor(), 'text' => $b->body, 'level' => $b->opt('level')])->values()->all();
    }

    /** A unique slug from the title (or the given text). */
    public static function uniqueSlug(string $text, ?string $ignoreId = null): string
    {
        $base = Str::slug(Str::limit($text, 90, '')) ?: 'post';
        $slug = $base;
        $n = 2;
        while (self::where('slug', $slug)->when($ignoreId, fn ($q) => $q->whereKeyNot($ignoreId))->exists()) {
            $slug = $base.'-'.$n++;
        }

        return $slug;
    }

    /** About 220 words a minute, plus a little time per image. */
    public function computeReadingMinutes(): int
    {
        $words = str_word_count(strip_tags(implode(' ', array_filter([
            $this->title, $this->excerpt,
            ...$this->blocks->map(fn ($b) => implode(' ', array_filter([$b->title, $b->body, $b->caption, ...collect($b->items ?? [])->flatten()->all()])))->all(),
        ]))));
        $images = $this->blocks->sum(fn ($b) => ($b->media_path ? 1 : 0) + count($b->media ?? []));

        return max(1, (int) round($words / 220 + $images * 0.15));
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
