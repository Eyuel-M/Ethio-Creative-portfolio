<?php

namespace App\Models;

use App\Models\Concerns\HasStatusMachine;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Application extends Model
{
    use HasStatusMachine, HasUlids;

    public const STATUSES = ['submitted', 'viewed', 'shortlisted', 'discussion', 'negotiation', 'accepted', 'rejected', 'withdrawn'];

    protected $guarded = ['id', 'status', 'viewed_at'];

    protected function casts(): array
    {
        return ['viewed_at' => 'datetime'];
    }

    public static function transitions(): array
    {
        return [
            'submitted' => ['viewed', 'shortlisted', 'discussion', 'rejected', 'withdrawn'],
            'viewed' => ['shortlisted', 'discussion', 'rejected', 'withdrawn'],
            'shortlisted' => ['discussion', 'negotiation', 'rejected', 'withdrawn'],
            'discussion' => ['shortlisted', 'negotiation', 'rejected', 'withdrawn'],
            'negotiation' => ['accepted', 'discussion', 'rejected', 'withdrawn'],
            'accepted' => [],
            'rejected' => [],
            'withdrawn' => [],
        ];
    }

    public function jobPost()
    {
        return $this->belongsTo(JobPost::class);
    }

    public function freelancer()
    {
        return $this->belongsTo(FreelancerProfile::class, 'freelancer_profile_id');
    }

    public function answers(): HasMany
    {
        return $this->hasMany(ApplicationAnswer::class);
    }

    public function portfolioProjects()
    {
        return $this->belongsToMany(PortfolioProject::class);
    }

    public function agreements(): HasMany
    {
        return $this->hasMany(AgreementVersion::class)->orderByDesc('version');
    }

    public function currentAgreement(): HasOne
    {
        return $this->hasOne(AgreementVersion::class)->where('status', '!=', 'superseded')->latestOfMany('version');
    }

    public function project(): HasOne
    {
        return $this->hasOne(Project::class);
    }

    public function conversation(): HasOne
    {
        return $this->hasOne(Conversation::class);
    }

    public function isParticipant(?User $user): bool
    {
        return $user && ($this->freelancer->user_id === $user->id || $this->jobPost->client->user_id === $user->id);
    }
}
