<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ScreeningQuestion extends Model
{
    public const TYPES = [
        'multiple_choice' => 'Multiple choice',
        'yes_no' => 'Yes / No',
        'multi_select' => 'Multi-select',
        'number' => 'Numeric amount',
        'text' => 'Written response',
        'portfolio_link' => 'Portfolio link',
        'file' => 'File upload',
    ];

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['options' => 'array', 'is_required' => 'boolean'];
    }

    public function hasOptions(): bool
    {
        return in_array($this->type, ['multiple_choice', 'multi_select'], true);
    }
}
