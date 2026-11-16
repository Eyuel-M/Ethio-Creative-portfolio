<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    protected $guarded = [];

    public function skills(): HasMany
    {
        return $this->hasMany(Skill::class);
    }

    public function jobPosts(): HasMany
    {
        return $this->hasMany(JobPost::class);
    }

    public function freelancers(): HasMany
    {
        return $this->hasMany(FreelancerProfile::class);
    }
}
