<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ClientProfile extends Model
{
    protected $guarded = ['id', 'user_id', 'is_verified'];

    protected $hidden = ['contact_phone'];

    protected function casts(): array
    {
        return ['is_verified' => 'boolean'];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function jobPosts(): HasMany
    {
        return $this->hasMany(JobPost::class);
    }

    public function displayName(): string
    {
        return $this->type === 'company' && $this->company_name ? $this->company_name : $this->user->displayName();
    }
}
