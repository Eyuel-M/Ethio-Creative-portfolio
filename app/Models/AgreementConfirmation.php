<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AgreementConfirmation extends Model
{
    public $timestamps = false;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['confirmed_at' => 'datetime'];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
