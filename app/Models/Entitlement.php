<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Entitlement extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['starts_at' => 'datetime', 'ends_at' => 'datetime'];
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function isActive(): bool
    {
        return $this->starts_at->isPast() && $this->ends_at->isFuture();
    }
}
