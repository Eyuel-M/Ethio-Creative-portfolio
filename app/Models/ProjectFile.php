<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

class ProjectFile extends Model
{
    use HasUlids;

    protected $guarded = ['id'];

    protected $hidden = ['object_key', 'disk'];

    protected function casts(): array
    {
        return ['receipt_confirmed_at' => 'datetime', 'delete_after' => 'datetime', 'preservation_hold' => 'boolean'];
    }

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploader_id');
    }

    public function events()
    {
        return $this->hasMany(FileEvent::class)->latest('created_at');
    }

    public function sizeLabel(): string
    {
        $b = $this->size_bytes;

        return $b >= 1048576 ? round($b / 1048576, 1).' MB' : max(1, round($b / 1024)).' KB';
    }
}
