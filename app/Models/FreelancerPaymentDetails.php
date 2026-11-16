<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * CBE / Telebirr details for direct client-to-freelancer payment. Encrypted at rest,
 * hidden from serialization, revealed only through ProjectDisclosure after mutual confirmation.
 */
class FreelancerPaymentDetails extends Model
{
    protected $table = 'freelancer_payment_details';

    protected $guarded = ['id'];

    protected $hidden = ['encrypted_cbe_account_number', 'encrypted_telebirr_number'];

    protected function casts(): array
    {
        return [
            'encrypted_cbe_account_number' => 'encrypted',
            'encrypted_telebirr_number' => 'encrypted',
        ];
    }

    public function profile()
    {
        return $this->belongsTo(FreelancerProfile::class, 'freelancer_profile_id');
    }

    public function refreshStatus(): void
    {
        $count = collect([$this->encrypted_cbe_account_number, $this->encrypted_telebirr_number])->filter()->count();
        $this->status = match (true) {
            $count === 0 => 'not_provided',
            filled($this->preferred_method) => 'complete',
            default => 'partial',
        };
    }

    public static function mask(?string $value): ?string
    {
        if (! $value) {
            return null;
        }

        return str_repeat('•', max(0, strlen($value) - 4)).substr($value, -4);
    }
}
