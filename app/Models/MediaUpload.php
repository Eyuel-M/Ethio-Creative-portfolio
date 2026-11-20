<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** An image uploaded ahead of saving; becomes part of a project when the project references its path. */
class MediaUpload extends Model
{
    /** Unused uploads older than this are deleted. */
    public const KEEP_HOURS = 24;

    protected $guarded = ['id'];

    /** Paths this user uploaded and hasn't used yet. */
    public static function pendingPaths(int $userId): array
    {
        return self::where('user_id', $userId)->pluck('path')->all();
    }

    /** Mark uploads as used by removing their pending rows. */
    public static function claim(int $userId, array $paths): void
    {
        $paths = array_values(array_filter($paths));
        if ($paths) {
            self::where('user_id', $userId)->whereIn('path', $paths)->delete();
        }
    }
}
