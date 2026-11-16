<?php

namespace App\Models;

use App\Models\Concerns\HasStatusMachine;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

/** Accepting an invitation means agreeing to discuss — never accepting the project. */
class Invitation extends Model
{
    use HasStatusMachine, HasUlids;

    protected $guarded = ['id', 'status', 'responded_at'];

    protected function casts(): array
    {
        return ['respond_by' => 'date', 'responded_at' => 'datetime'];
    }

    public static function transitions(): array
    {
        return [
            'pending' => ['accepted', 'declined', 'withdrawn', 'expired'],
            'accepted' => [], 'declined' => [], 'withdrawn' => [], 'expired' => [],
        ];
    }

    public function jobPost()
    {
        return $this->belongsTo(JobPost::class);
    }

    public function freelancer()
    {
        return $this->belongsTo(FreelancerProfile::class, 'freelancer_profile_id');
    }
}
