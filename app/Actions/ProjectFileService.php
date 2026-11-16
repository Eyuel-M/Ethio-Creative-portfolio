<?php

namespace App\Actions;

use App\Models\Project;
use App\Models\ProjectFile;
use App\Models\User;
use App\Notifications\MarketplaceNotification;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Project file exchange. Development stores bytes on the private local disk; production is intended to use
 * Cloudflare R2 with short-lived presigned URLs ONLY after the data-residency/legal review gate (spec §9, §15.4).
 * Download is not acceptance; receipt confirmation is receipt only.
 */
class ProjectFileService
{
    public function disk(): string
    {
        return config('marketplace.project_files.disk', 'local');
    }

    public function store(Project $project, User $user, UploadedFile $file): ProjectFile
    {
        $key = 'projects/'.Str::random(48);
        Storage::disk($this->disk())->putFileAs(dirname($key), $file, basename($key));

        $pf = ProjectFile::create([
            'project_id' => $project->id,
            'uploader_id' => $user->id,
            'disk' => $this->disk(),
            'object_key' => $key,
            'original_name' => Str::limit($file->getClientOriginalName(), 200, ''),
            'mime_type' => $file->getMimeType() ?? 'application/octet-stream',
            'size_bytes' => $file->getSize(),
            'sha256' => hash_file('sha256', $file->getRealPath()),
            'delete_after' => now()->addDays((int) config('marketplace.project_files.unconfirmed_retention_days')),
        ]);
        $pf->events()->create(['actor_id' => $user->id, 'type' => 'uploaded']);
        $project->log('file_uploaded', "{$user->name} uploaded {$pf->original_name}", $user->id);

        $recipient = $user->id === $project->client_user_id ? $project->freelancer : $project->client;
        $recipient->notify(new MarketplaceNotification('New project file', "{$user->name} shared a file on “{$project->title}”.", route('projects.show', $project), 'file'));

        return $pf;
    }

    public function confirmReceipt(ProjectFile $file, User $user): void
    {
        $file->update([
            'status' => 'receipt_confirmed',
            'receipt_confirmed_at' => now(),
            'delete_after' => now()->addDays((int) config('marketplace.project_files.confirmed_retention_days')),
        ]);
        $file->events()->create(['actor_id' => $user->id, 'type' => 'receipt_confirmed', 'note' => 'Receipt only — not approval of quality.']);
        $file->project->log('file_receipt', "{$user->name} confirmed receipt of {$file->original_name}", $user->id);
    }
}
