<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use App\Models\FreelancerProfile;
use App\Models\Invitation;
use App\Notifications\MarketplaceNotification;
use Illuminate\Http\Request;

class InvitationController extends Controller
{
    public function index(Request $request)
    {
        $invitations = $request->user()->freelancerProfile->invitations()->with(['jobPost.client.user', 'jobPost.category'])->latest()->paginate(12);

        return view('invitations.index', compact('invitations'));
    }

    public function store(Request $request, FreelancerProfile $profile)
    {
        $data = $request->validate([
            'job_post_id' => 'required|string',
            'message' => 'nullable|string|max:2000',
            'respond_by' => 'nullable|date|after:today',
        ]);
        $job = $request->user()->clientProfile->jobPosts()->where('status', 'published')->findOrFail($data['job_post_id']);
        abort_if($profile->user_id === $request->user()->id, 422);

        $invitation = Invitation::firstOrNew(['job_post_id' => $job->id, 'freelancer_profile_id' => $profile->id]);
        if ($invitation->exists) {
            return back()->with('status', 'This freelancer has already been invited to that job.');
        }
        $invitation->fill(['message' => $data['message'] ?? null, 'respond_by' => $data['respond_by'] ?? null]);
        $invitation->status = 'pending';
        $invitation->save();

        $profile->user->notify(new MarketplaceNotification('You were invited to a job', "{$request->user()->name} invited you to discuss “{$job->title}”.", route('invitations.index'), 'mail'));

        return back()->with('status', 'Invitation sent.');
    }

    public function respond(Request $request, Invitation $invitation)
    {
        abort_unless($invitation->freelancer_profile_id === $request->user()->freelancerProfile->id, 403);
        $data = $request->validate(['response' => 'required|in:accepted,declined']);
        $invitation->transitionTo($data['response'], $request->user()->id);
        $invitation->forceFill(['responded_at' => now()])->save();
        $job = $invitation->jobPost;

        $job->client->user->notify(new MarketplaceNotification('Invitation '.$data['response'], "{$request->user()->name} {$data['response']} your invitation for “{$job->title}”.", route('client.jobs.applicants', $job), 'mail'));

        if ($data['response'] === 'accepted') {
            // Accepting means agreeing to discuss — not accepting the project. Contact details stay private.
            $conversation = Conversation::firstOrCreate(
                ['client_user_id' => $job->client->user_id, 'freelancer_user_id' => $request->user()->id, 'job_post_id' => $job->id],
                ['invitation_id' => $invitation->id, 'last_message_at' => now()]
            );

            return redirect()->route('messages.show', $conversation)->with('status', 'Invitation accepted. You can now discuss the opportunity. Apply to the job when you are ready to propose terms.');
        }

        return back()->with('status', 'Invitation declined.');
    }
}
