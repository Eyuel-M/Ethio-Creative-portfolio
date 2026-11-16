<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

/** Platform-service order only. Never used for client-to-freelancer project money. */
class Order extends Model
{
    use HasUlids;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['paid_at' => 'datetime'];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function jobPost()
    {
        return $this->belongsTo(JobPost::class);
    }

    public function entitlement()
    {
        return $this->hasOne(Entitlement::class);
    }
}
