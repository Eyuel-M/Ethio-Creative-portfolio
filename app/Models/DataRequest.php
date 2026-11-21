<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DataRequest extends Model
{
    protected $guarded = ['id', 'status', 'handled_by', 'handled_at', 'export_path'];

    protected function casts(): array
    {
        return ['handled_at' => 'datetime', 'export_expires_at' => 'datetime'];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function handler()
    {
        return $this->belongsTo(User::class, 'handled_by');
    }

    public function exportAvailable(): bool
    {
        return $this->type === 'export' && $this->status === 'completed' && $this->export_path && $this->export_expires_at?->isFuture();
    }
}
