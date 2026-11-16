<?php

namespace App\Models;

use App\Models\Concerns\HasStatusMachine;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

class Project extends Model
{
    use HasStatusMachine, HasUlids;

    protected $guarded = ['id', 'status'];

    protected function casts(): array
    {
        return ['completed_at' => 'datetime'];
    }

    public static function transitions(): array
    {
        return [
            'in_progress' => ['submitted_for_review', 'cancellation_requested'],
            'submitted_for_review' => ['revision_requested', 'delivered', 'cancellation_requested'],
            'revision_requested' => ['submitted_for_review', 'cancellation_requested'],
            'delivered' => ['completed', 'revision_requested'],
            'cancellation_requested' => ['cancelled', 'in_progress'],
            'completed' => [],
            'cancelled' => [],
        ];
    }

    public function application()
    {
        return $this->belongsTo(Application::class);
    }

    public function agreement()
    {
        return $this->belongsTo(AgreementVersion::class, 'agreement_version_id');
    }

    public function client()
    {
        return $this->belongsTo(User::class, 'client_user_id');
    }

    public function freelancer()
    {
        return $this->belongsTo(User::class, 'freelancer_user_id');
    }

    public function milestones()
    {
        return $this->hasMany(Milestone::class)->orderBy('sort_order');
    }

    public function events()
    {
        return $this->hasMany(ProjectEvent::class)->latest('created_at')->latest('id');
    }

    public function files()
    {
        return $this->hasMany(ProjectFile::class)->latest();
    }

    public function reviews()
    {
        return $this->hasMany(Review::class);
    }

    public function scopeFor($q, User $user)
    {
        return $q->where(fn ($w) => $w->where('client_user_id', $user->id)->orWhere('freelancer_user_id', $user->id));
    }

    public function hasParticipant(?User $user): bool
    {
        return $user !== null && in_array($user->id, [$this->client_user_id, $this->freelancer_user_id], true);
    }

    public function progress(): int
    {
        $total = $this->milestones->count();
        if ($this->status === 'completed') {
            return 100;
        }

        return $total ? (int) round($this->milestones->where('status', 'accepted')->count() / $total * 100) : 0;
    }

    public function log(string $type, string $description, ?int $actorId = null): void
    {
        $this->events()->create(['type' => $type, 'description' => $description, 'actor_id' => $actorId]);
    }
}
