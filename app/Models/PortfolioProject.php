<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PortfolioProject extends Model
{
    use HasUlids;

    protected $guarded = ['id', 'freelancer_profile_id', 'views', 'published_at', 'status'];

    protected function casts(): array
    {
        return ['tags' => 'array', 'project_date' => 'date', 'published_at' => 'datetime'];
    }

    public function profile()
    {
        return $this->belongsTo(FreelancerProfile::class, 'freelancer_profile_id');
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function blocks(): HasMany
    {
        return $this->hasMany(PortfolioBlock::class)->orderBy('sort_order');
    }

    public function likes()
    {
        return $this->belongsToMany(User::class, 'portfolio_likes');
    }

    /** Published, owned by a public active profile — i.e. safe to show to anyone. */
    public function scopeDiscoverable($q)
    {
        return $q->where('status', 'published')->whereHas('profile', fn ($p) => $p->public());
    }

    public function isPublished(): bool
    {
        return $this->status === 'published';
    }

    public function coverUrl(): ?string
    {
        return $this->cover_path ? \App\Support\Media::url($this->cover_path) : null;
    }
}
