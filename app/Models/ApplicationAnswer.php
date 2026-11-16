<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ApplicationAnswer extends Model
{
    protected $guarded = ['id'];

    public function question()
    {
        return $this->belongsTo(ScreeningQuestion::class, 'screening_question_id');
    }

    public function display(): string
    {
        $decoded = json_decode((string) $this->answer, true);

        return is_array($decoded) ? implode(', ', $decoded) : (string) $this->answer;
    }
}
