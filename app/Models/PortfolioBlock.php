<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PortfolioBlock extends Model
{
    protected $guarded = ['id'];

    public function mediaUrl(): ?string
    {
        return $this->media_path ? \App\Support\Media::url($this->media_path) : null;
    }

    /** Only allowlisted video providers are embedded; arbitrary iframe HTML is never accepted. */
    public function embedSrc(): ?string
    {
        $url = (string) $this->embed_url;
        if (preg_match('~(?:youtube\.com/watch\?v=|youtu\.be/)([\w-]{11})~', $url, $m)) {
            return 'https://www.youtube-nocookie.com/embed/'.$m[1];
        }
        if (preg_match('~vimeo\.com/(\d+)~', $url, $m)) {
            return 'https://player.vimeo.com/video/'.$m[1];
        }

        return null;
    }
}
