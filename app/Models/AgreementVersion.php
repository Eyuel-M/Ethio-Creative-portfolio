<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

/** Immutable once created. Any change produces a new version that both parties must confirm again. */
class AgreementVersion extends Model
{
    use HasUlids;

    public const TERM_FIELDS = ['title', 'scope', 'deliverables', 'amount_minor', 'currency', 'deadline', 'revisions', 'cancellation_terms', 'milestones'];

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['deadline' => 'date', 'milestones' => 'array'];
    }

    public function application()
    {
        return $this->belongsTo(Application::class);
    }

    public function author()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function confirmations()
    {
        return $this->hasMany(AgreementConfirmation::class);
    }

    public static function hashTerms(array $terms): string
    {
        $data = collect(self::TERM_FIELDS)->mapWithKeys(fn ($f) => [$f => $terms[$f] ?? null])->all();
        if ($data['deadline'] instanceof \DateTimeInterface) {
            $data['deadline'] = $data['deadline']->format('Y-m-d');
        }

        return hash('sha256', json_encode($data));
    }

    public function computedHash(): string
    {
        return self::hashTerms($this->only(self::TERM_FIELDS));
    }

    public function confirmedBy(User $user): bool
    {
        return $this->confirmations->contains('user_id', $user->id);
    }
}
