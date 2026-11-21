<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Review extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['hidden_at' => 'datetime'];
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }

    public function project()
    {
        return $this->belongsTo(Project::class);
    }
}
