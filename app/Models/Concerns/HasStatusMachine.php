<?php

namespace App\Models\Concerns;

use App\Models\StatusHistory;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Server-side state machine: invalid transitions throw and make no partial change;
 * every transition records actor, old state, new state and reason.
 */
trait HasStatusMachine
{
    abstract public static function transitions(): array;

    public function canTransitionTo(string $to): bool
    {
        return in_array($to, static::transitions()[$this->status] ?? [], true);
    }

    public function transitionTo(string $to, ?int $actorId = null, ?string $reason = null): static
    {
        if (! $this->canTransitionTo($to)) {
            throw ValidationException::withMessages([
                'status' => "Cannot move from “{$this->status}” to “{$to}”.",
            ]);
        }

        DB::transaction(function () use ($to, $actorId, $reason) {
            $from = $this->status;
            $this->status = $to;
            $this->save();
            StatusHistory::create([
                'subject_type' => static::class,
                'subject_id' => (string) $this->getKey(),
                'from_status' => $from,
                'to_status' => $to,
                'actor_id' => $actorId,
                'reason' => $reason,
            ]);
        });

        return $this;
    }

    public function statusHistory()
    {
        return $this->hasMany(StatusHistory::class, 'subject_id')->where('subject_type', static::class)->latest('created_at');
    }
}
