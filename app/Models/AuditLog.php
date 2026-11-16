<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    public $timestamps = false;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['meta' => 'array', 'created_at' => 'datetime'];
    }

    public function actor()
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    public static function record(string $action, $target = null, ?string $reason = null, array $meta = []): self
    {
        return static::create([
            'actor_id' => auth()->id(),
            'action' => $action,
            'target_type' => $target ? class_basename($target) : null,
            'target_id' => $target ? (string) $target->getKey() : null,
            'reason' => $reason,
            'meta' => $meta ?: null,
            'ip_address' => request()?->ip(),
        ]);
    }
}
