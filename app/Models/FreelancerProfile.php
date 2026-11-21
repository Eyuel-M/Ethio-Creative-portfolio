<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class FreelancerProfile extends Model
{
    protected $guarded = ['id', 'user_id', 'plan', 'featured_until', 'profile_views'];

    // Private contact fields must never reach public HTML, JSON or caches.
    protected $hidden = ['contact_phone', 'contact_email'];

    protected function casts(): array
    {
        return [
            'languages' => 'array',
            'tools' => 'array',
            'is_public' => 'boolean',
            'featured_until' => 'datetime',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function skills(): BelongsToMany
    {
        return $this->belongsToMany(Skill::class);
    }

    public function portfolioProjects(): HasMany
    {
        return $this->hasMany(PortfolioProject::class)->orderBy('sort_order')->orderByDesc('created_at');
    }

    public function publishedProjects(): HasMany
    {
        return $this->portfolioProjects()->where('status', 'published');
    }

    public function paymentDetails(): HasOne
    {
        return $this->hasOne(FreelancerPaymentDetails::class);
    }

    public function applications(): HasMany
    {
        return $this->hasMany(Application::class);
    }

    public function invitations(): HasMany
    {
        return $this->hasMany(Invitation::class);
    }

    public function scopePublic(Builder $q): Builder
    {
        return $q->where('is_public', true)->whereHas('user', fn ($u) => $u->where('status', 'active'));
    }

    public function isFeatured(): bool
    {
        return $this->featured_until !== null && $this->featured_until->isFuture();
    }

    public function portfolioLimit(): int
    {
        return $this->plan === 'professional' ? (int) config('marketplace.portfolio_limits.professional') : (int) config('marketplace.portfolio_limits.free');
    }

    public function completion(): int
    {
        $checks = [
            filled($this->title), filled($this->bio), filled($this->location), $this->category_id !== null,
            $this->skills()->exists(), $this->user->avatar_path !== null, $this->publishedProjects()->exists(),
            optional($this->paymentDetails)->status !== null && optional($this->paymentDetails)->status !== 'not_provided',
        ];

        return (int) round(collect($checks)->filter()->count() / count($checks) * 100);
    }

    public function rating(): array
    {
        $q = Review::where('reviewee_id', $this->user_id)->whereNull('hidden_at');

        return ['avg' => round((float) $q->avg('rating'), 1), 'count' => $q->count()];
    }

    public function coverUrl(): ?string
    {
        return $this->cover_path ? \App\Support\Media::url($this->cover_path) : null;
    }
}
