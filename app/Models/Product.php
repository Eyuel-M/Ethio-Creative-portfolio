<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['benefits' => 'array', 'limits' => 'array', 'is_active' => 'boolean'];
    }

    public function priceLabel(): string
    {
        return \App\Support\Money::format($this->price_minor, $this->currency);
    }
}
