<?php

namespace App\Models;

use App\Models\Concerns\HasStatusMachine;
use Illuminate\Database\Eloquent\Model;

/** Milestone acceptance is a project record only; it never transfers money. */
class Milestone extends Model
{
    use HasStatusMachine;

    protected $guarded = ['id', 'status'];

    protected function casts(): array
    {
        return ['due_date' => 'date'];
    }

    public static function transitions(): array
    {
        return [
            'not_started' => ['in_progress', 'cancelled'],
            'in_progress' => ['submitted', 'cancelled'],
            'submitted' => ['accepted', 'revision_requested'],
            'revision_requested' => ['submitted', 'cancelled'],
            'accepted' => [],
            'cancelled' => [],
        ];
    }

    public function project()
    {
        return $this->belongsTo(Project::class);
    }
}
