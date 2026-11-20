<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Skill extends Model
{
    protected $guarded = [];

    public function freelancerProfiles()
    {
        return $this->belongsToMany(FreelancerProfile::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }
}
