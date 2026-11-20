<?php

namespace App\Http\Controllers;

use App\Models\MediaUpload;
use App\Support\Media;
use Illuminate\Http\Request;

/** Receives one image at a time from the case-study builder, compresses it and parks it until the project is saved. */
class MediaUploadController extends Controller
{
    public function store(Request $request)
    {
        $max = config('marketplace.max_upload_kb.image');
        $request->validate([
            'image' => "required|image|mimes:jpg,jpeg,png,webp,gif|max:{$max}|dimensions:max_width=8000,max_height=8000",
        ]);

        $stored = Media::storeWithInfo($request->file('image'), 'portfolio');
        MediaUpload::create(['user_id' => $request->user()->id] + $stored);

        return response()->json([
            'path' => $stored['path'],
            'url' => Media::url($stored['path']),
            'bytes' => $stored['bytes'],
            'original_bytes' => $stored['original_bytes'],
        ], 201);
    }
}
