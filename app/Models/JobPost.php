<?php

namespace App\Models;

use App\Models\Concerns\HasStatusMachine;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class JobPost extends Model
{
    use HasStatusMachine, HasUlids;

    protected $guarded = ['id', 'client_profile_id', 'status', 'is_featured', 'featured_until', 'published_at', 'expires_at'];

    protected function casts(): array
    {
        return [
            'deadline' => 'date',
            'application_deadline' => 'date',
            'published_at' => 'datetime',
            'expires_at' => 'datetime',
            'featured_until' => 'datetime',
            'is_featured' => 'boolean',
            'is_remote' => 'boolean',
        ];
    }

    public static function transitions(): array
    {
        return [
            'draft' => ['pending_payment', 'published'],
            'pending_payment' => ['published', 'draft'],
            'published' => ['paused', 'filled', 'closed', 'expired', 'removed'],
            'paused' => ['published', 'closed', 'filled'],
            'expired' => ['pending_payment', 'published'],
            'filled' => [],
            'closed' => [],
            'removed' => [],
        ];
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(ClientProfile::class, 'client_profile_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function skills(): BelongsToMany
    {
        return $this->belongsToMany(Skill::class);
    }

    public function questions(): HasMany
    {
        return $this->hasMany(ScreeningQuestion::class)->orderBy('sort_order');
    }

    public function applications(): HasMany
    {
        return $this->hasMany(Application::class);
    }

    public function invitations(): HasMany
    {
        return $this->hasMany(Invitation::class);
    }

    public function scopeLive(Builder $q): Builder
    {
        return $q->where('status', 'published')->where(fn ($w) => $w->whereNull('expires_at')->orWhere('expires_at', '>', now()));
    }

    public function isLive(): bool
    {
        return $this->status === 'published' && ($this->expires_at === null || $this->expires_at->isFuture());
    }

    public function isAcceptingApplications(): bool
    {
        return $this->isLive() && ($this->application_deadline === null || $this->application_deadline->endOfDay()->isFuture());
    }

    public function isFeatured(): bool
    {
        return $this->is_featured && ($this->featured_until === null || $this->featured_until->isFuture());
    }

    public function budgetLabel(): string
    {
        $fmt = fn ($m) => \App\Support\Money::format($m, $this->currency);
        $suffix = $this->budget_type === 'hourly' ? ' /hour' : '';
        if ($this->budget_min_minor && $this->budget_max_minor && $this->budget_min_minor !== $this->budget_max_minor) {
            return $fmt($this->budget_min_minor).' – '.$fmt($this->budget_max_minor).$suffix;
        }
        $v = $this->budget_max_minor ?: $this->budget_min_minor;

        return $v ? $fmt($v).$suffix : 'Budget negotiable';
    }

    public function ownedBy(?User $user): bool
    {
        return $user !== null && $this->client?->user_id === $user->id;
    }
}
