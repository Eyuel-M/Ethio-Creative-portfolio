<?php

namespace App\Http\Controllers;

use App\Actions\ProjectDisclosure;
use App\Actions\ProjectFileService;
use App\Models\Milestone;
use App\Models\Project;
use App\Models\ProjectFile;
use App\Models\Review;
use App\Notifications\MarketplaceNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProjectController extends Controller
{
    public function index(Request $request)
    {
        $projects = Project::for($request->user())->with(['client.clientProfile', 'freelancer', 'milestones', 'agreement'])->latest()->paginate(12);

        return view('projects.index', compact('projects'));
    }

    public function show(Request $request, Project $project, ProjectDisclosure $disclosure)
    {
        abort_unless($project->hasParticipant($request->user()), 403);
        $project->load(['agreement.confirmations.user', 'milestones', 'events.actor', 'files.uploader', 'files.events', 'reviews.reviewer', 'client.clientProfile', 'freelancer.freelancerProfile', 'application.jobPost']);
        $isClient = $project->client_user_id === $request->user()->id;
        $private = $disclosure->for($project, $request->user());
        $myReview = $project->reviews->firstWhere('reviewer_id', $request->user()->id);
        $conversation = $project->application->conversation;

        return view('projects.show', compact('project', 'isClient', 'private', 'myReview', 'conversation'));
    }

    public function status(Request $request, Project $project)
    {
        $user = $request->user();
        abort_unless($project->hasParticipant($user), 403);
        $isClient = $project->client_user_id === $user->id;
        $allowed = $isClient
            ? ['revision_requested', 'completed', 'cancellation_requested', 'cancelled', 'in_progress']
            : ['submitted_for_review', 'delivered', 'cancellation_requested', 'cancelled', 'in_progress'];
        $data = $request->validate(['status' => 'required|in:'.implode(',', $allowed), 'reason' => 'nullable|string|max:500']);

        // Cancellation: requester gives a reason; the OTHER party responds (accept → cancelled, decline → in_progress).
        if ($data['status'] === 'cancellation_requested') {
            $request->validate(['reason' => 'required|string|min:5|max:500']);
        }
        if (in_array($data['status'], ['cancelled', 'in_progress'], true) && $project->status === 'cancellation_requested') {
            $requester = $project->statusHistory()->where('to_status', 'cancellation_requested')->first()?->actor_id;
            abort_if($requester === $user->id, 403, 'The other party must respond to your cancellation request.');
        }

        $project->transitionTo($data['status'], $user->id, $data['reason'] ?? null);
        if ($data['status'] === 'completed') {
            $project->forceFill(['completed_at' => now()])->save();
        }
        $label = str_replace('_', ' ', $data['status']);
        $project->log('status', "{$user->name} set status to {$label}".(($data['reason'] ?? null) ? " — {$data['reason']}" : ''), $user->id);

        $other = $isClient ? $project->freelancer : $project->client;
        $other->notify(new MarketplaceNotification('Project update', "“{$project->title}” is now {$label}.", route('projects.show', $project), 'folder'));

        return back()->with('status', 'Project updated.');
    }

    public function milestone(Request $request, Project $project, Milestone $milestone)
    {
        $user = $request->user();
        abort_unless($project->hasParticipant($user) && $milestone->project_id === $project->id, 403);
        $isClient = $project->client_user_id === $user->id;
        $allowed = $isClient ? ['accepted', 'revision_requested'] : ['in_progress', 'submitted'];
        $data = $request->validate(['status' => 'required|in:'.implode(',', $allowed)]);
        $milestone->transitionTo($data['status'], $user->id);
        $project->log('milestone', "{$user->name} marked “{$milestone->title}” as ".str_replace('_', ' ', $data['status']), $user->id);

        return back()->with('status', 'Milestone updated. Milestone status is a record only — it does not move money.');
    }

    public function upload(Request $request, Project $project, ProjectFileService $files)
    {
        abort_unless($project->hasParticipant($request->user()), 403);
        abort_if(in_array($project->status, ['cancelled'], true), 422, 'Project is closed.');
        $max = config('marketplace.max_upload_kb.project_file');
        $request->validate(['file' => 'required|file|max:'.$max.'|mimes:'.config('marketplace.project_files.allowed_mimes')]);
        $files->store($project, $request->user(), $request->file('file'));

        return back()->with('status', 'File uploaded. The other party has been notified.');
    }

    public function download(Request $request, ProjectFile $file)
    {
        $project = $file->project;
        abort_unless($project->hasParticipant($request->user()), 403);
        abort_if($file->status === 'deleted', 410);
        // Records the authorization, not proof that the file was opened or accepted.
        $file->events()->create(['actor_id' => $request->user()->id, 'type' => 'download_authorized']);

        return Storage::disk($file->disk)->download($file->object_key, $file->original_name);
    }

    public function receipt(Request $request, ProjectFile $file, ProjectFileService $files)
    {
        abort_unless($file->project->hasParticipant($request->user()) && $file->uploader_id !== $request->user()->id, 403);
        $data = $request->validate(['action' => 'required|in:confirm,problem', 'note' => 'nullable|string|max:300']);
        if ($data['action'] === 'confirm') {
            $files->confirmReceipt($file, $request->user());
        } else {
            $file->update(['status' => 'problem_reported']);
            $file->events()->create(['actor_id' => $request->user()->id, 'type' => 'problem_reported', 'note' => $data['note'] ?? null]);
            $file->project->log('file_problem', "{$request->user()->name} reported a problem with {$file->original_name}", $request->user()->id);
        }

        return back()->with('status', $data['action'] === 'confirm' ? 'Receipt confirmed (receipt only — not approval of the work).' : 'Problem reported to the uploader.');
    }

    public function review(Request $request, Project $project)
    {
        $user = $request->user();
        abort_unless($project->hasParticipant($user), 403);
        abort_unless(in_array($project->status, ['completed', 'cancelled'], true), 422, 'Reviews open once the project is completed or closed.');
        $data = $request->validate(['rating' => 'required|integer|min:1|max:5', 'body' => 'nullable|string|max:2000']);
        abort_if($project->reviews()->where('reviewer_id', $user->id)->exists(), 422, 'You already reviewed this project.');

        Review::create([
            'project_id' => $project->id,
            'reviewer_id' => $user->id,
            'reviewee_id' => $user->id === $project->client_user_id ? $project->freelancer_user_id : $project->client_user_id,
            'rating' => $data['rating'],
            'body' => $data['body'] ?? null,
        ]);
        $project->log('review', "{$user->name} left a review", $user->id);

        return back()->with('status', 'Thanks — your review was recorded.');
    }
}
