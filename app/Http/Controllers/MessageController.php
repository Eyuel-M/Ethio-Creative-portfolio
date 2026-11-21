<?php

namespace App\Http\Controllers;

use App\Models\Application;
use App\Models\Conversation;
use App\Notifications\MarketplaceNotification;
use Illuminate\Http\Request;

class MessageController extends Controller
{
    public function index(Request $request)
    {
        $conversations = Conversation::for($request->user())->with(['client', 'freelancer', 'jobPost', 'latestMessage'])->orderByDesc('last_message_at')->get();
        return view('messages.index', ['conversations' => $conversations, 'active' => null, 'messages' => collect()]);
    }

    public function show(Request $request, Conversation $conversation)
    {
        abort_unless($conversation->hasParticipant($request->user()), 403);
        $conversation->messages()->whereNull('read_at')->where('user_id', '!=', $request->user()->id)->update(['read_at' => now()]);
        $conversations = Conversation::for($request->user())->with(['client', 'freelancer', 'jobPost', 'latestMessage'])->orderByDesc('last_message_at')->get();
        $messages = $conversation->messages()->with('user')->latest()->take(100)->get()->reverse()->values();
        $conversation->load(['jobPost', 'application', 'project']);

        return view('messages.index', ['conversations' => $conversations, 'active' => $conversation, 'messages' => $messages]);
    }

    public function store(Request $request, Conversation $conversation)
    {
        abort_unless($conversation->hasParticipant($request->user()), 403);
        $data = $request->validate(['body' => 'required|string|max:4000']);
        $conversation->messages()->create(['user_id' => $request->user()->id, 'body' => $data['body']]);
        \App\Support\Analytics::track('message_sent');
        $conversation->update(['last_message_at' => now()]);

        $other = $conversation->otherParty($request->user());
        $other->notify(new MarketplaceNotification('New message', "{$request->user()->name} sent you a message.", route('messages.show', $conversation), 'message'));

        return redirect()->route('messages.show', $conversation);
    }

    public function startFromApplication(Request $request, Application $application)
    {
        abort_unless($application->isParticipant($request->user()), 403);
        $job = $application->jobPost;
        $isClient = $job->client->user_id === $request->user()->id;
        // Pre-hire conversations are tied to an application; the client opens them ("invite to chat").
        if ($isClient && in_array($application->status, ['submitted', 'viewed', 'shortlisted'], true)) {
            $application->transitionTo('discussion', $request->user()->id, 'Client invited to chat');
        }
        abort_unless($isClient || $application->conversation, 403, 'The client opens the conversation.');

        $conversation = Conversation::firstOrCreate(
            ['client_user_id' => $job->client->user_id, 'freelancer_user_id' => $application->freelancer->user_id, 'job_post_id' => $job->id],
            ['last_message_at' => now()]
        );
        $conversation->update(['application_id' => $application->id]);

        return redirect()->route('messages.show', $conversation);
    }
}
