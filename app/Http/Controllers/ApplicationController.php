<?php

namespace App\Http\Controllers;

use App\Actions\AgreementService;
use App\Models\AgreementVersion;
use App\Models\Application;
use App\Models\JobPost;
use App\Notifications\MarketplaceNotification;
use App\Support\Money;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class ApplicationController extends Controller
{
    public function mine(Request $request)
    {
        $status = $request->query('status');
        $applications = $request->user()->freelancerProfile->applications()
            ->when($status, fn ($q) => $q->where('status', $status))
            ->with(['jobPost.client.user', 'jobPost.category'])->latest()->paginate(12)->withQueryString();
        $counts = $request->user()->freelancerProfile->applications()->selectRaw('status, count(*) c')->groupBy('status')->pluck('c', 'status');

        return view('applications.mine', compact('applications', 'counts', 'status'));
    }

    public function create(Request $request, JobPost $job)
    {
        $profile = $request->user()->freelancerProfile;
        $this->guardCanApply($request, $job);
        $job->load(['questions', 'client.user', 'skills']);
        $portfolio = $profile->publishedProjects()->get();

        return view('applications.create', compact('job', 'portfolio', 'profile'));
    }

    public function store(Request $request, JobPost $job)
    {
        $profile = $request->user()->freelancerProfile;
        $this->guardCanApply($request, $job);
        $job->load('questions');

        $rules = [
            'cover_message' => 'required|string|min:30|max:5000',
            'proposed_amount' => 'required|numeric|min:1|max:100000000',
            'currency' => 'required|in:'.implode(',', config('marketplace.currencies')),
            'estimated_days' => 'required|integer|min:1|max:730',
            'availability' => 'nullable|string|max:120',
            'portfolio_ids' => 'nullable|array|max:6',
            'portfolio_ids.*' => 'string',
        ];
        foreach ($job->questions as $q) {
            $key = "answers.{$q->id}";
            $req = $q->is_required ? 'required' : 'nullable';
            $rules[$key] = match ($q->type) {
                'multiple_choice' => [$req, 'string', 'in:'.implode(',', array_map(fn ($o) => str_replace(',', '\,', $o), $q->options ?? []))],
                'multi_select' => [$req, 'array'],
                'yes_no' => [$req, 'in:Yes,No'],
                'number' => [$req, 'numeric', 'min:0', 'max:1000000000'],
                'portfolio_link' => [$req, 'url', 'max:300'],
                'file' => [$req, 'file', 'max:10240', 'mimes:pdf,png,jpg,jpeg,webp,zip,docx'],
                default => [$req, 'string', 'max:3000'],
            };
            if ($q->type === 'multi_select') {
                $rules["{$key}.*"] = ['string', 'in:'.implode(',', array_map(fn ($o) => str_replace(',', '\,', $o), $q->options ?? []))];
            }
        }
        $data = $request->validate($rules, [], ['answers.*' => 'answer']);

        $portfolioIds = $profile->publishedProjects()->whereIn('id', $data['portfolio_ids'] ?? [])->pluck('id');

        $application = DB::transaction(function () use ($job, $profile, $data, $portfolioIds, $request) {
            if (Application::where('job_post_id', $job->id)->where('freelancer_profile_id', $profile->id)->lockForUpdate()->exists()) {
                throw ValidationException::withMessages(['cover_message' => 'You have already applied to this job.']);
            }
            $application = new Application([
                'cover_message' => $data['cover_message'],
                'proposed_amount_minor' => Money::toMinor($data['proposed_amount']),
                'currency' => $data['currency'],
                'estimated_days' => $data['estimated_days'],
                'availability' => $data['availability'] ?? null,
            ]);
            $application->job_post_id = $job->id;
            $application->freelancer_profile_id = $profile->id;
            $application->status = 'submitted';
            $application->save();
            $application->portfolioProjects()->sync($portfolioIds);

            foreach ($job->questions as $q) {
                $val = $data['answers'][$q->id] ?? null;
                if ($val === null) {
                    continue;
                }
                $answer = ['screening_question_id' => $q->id];
                if ($q->type === 'file') {
                    $answer['file_path'] = $request->file("answers.{$q->id}")->store('application-files', 'local');
                    $answer['answer'] = $request->file("answers.{$q->id}")->getClientOriginalName();
                } else {
                    $answer['answer'] = is_array($val) ? json_encode(array_values($val)) : (string) $val;
                }
                $application->answers()->create($answer);
            }

            return $application;
        });

        $job->client->user->notify(new MarketplaceNotification(
            'New application',
            "{$request->user()->name} applied to “{$job->title}”.",
            route('applications.show', $application),
            'file'
        ));

        return redirect()->route('applications.show', $application)->with('status', 'Application submitted. You can track its status here.');
    }

    public function show(Request $request, Application $application)
    {
        $application->load(['jobPost.client.user', 'jobPost.questions', 'freelancer.user', 'freelancer.skills', 'answers.question', 'portfolioProjects', 'agreements.confirmations.user', 'agreements.author', 'project', 'conversation']);
        $user = $request->user();
        abort_unless($application->isParticipant($user), 403);
        $isClient = $application->jobPost->client->user_id === $user->id;

        if ($isClient && $application->status === 'submitted') {
            $application->transitionTo('viewed', $user->id);
            $application->forceFill(['viewed_at' => now()])->save();
        }

        $history = $application->statusHistory()->with('actor')->get();

        return view('applications.show', compact('application', 'isClient', 'history'));
    }

    public function status(Request $request, Application $application)
    {
        $user = $request->user();
        abort_unless($application->isParticipant($user), 403);
        $isClient = $application->jobPost->client->user_id === $user->id;
        $allowed = $isClient ? ['shortlisted', 'discussion', 'rejected'] : ['withdrawn'];
        $data = $request->validate(['status' => 'required|in:'.implode(',', $allowed), 'reason' => 'nullable|string|max:300']);

        $application->transitionTo($data['status'], $user->id, $data['reason'] ?? null);

        $recipient = $isClient ? $application->freelancer->user : $application->jobPost->client->user;
        $recipient->notify(new MarketplaceNotification(
            'Application update',
            "Application for “{$application->jobPost->title}” is now ".str_replace('_', ' ', $data['status']).'.',
            route('applications.show', $application),
            'file'
        ));

        return back()->with('status', 'Status updated.');
    }

    public function proposeAgreement(Request $request, Application $application, AgreementService $service)
    {
        abort_unless($application->isParticipant($request->user()), 403);
        $d = $request->validate([
            'title' => 'required|string|max:160',
            'scope' => 'required|string|max:5000',
            'deliverables' => 'required|string|max:3000',
            'amount' => 'required|numeric|min:1|max:100000000',
            'currency' => 'required|in:'.implode(',', config('marketplace.currencies')),
            'deadline' => 'required|date|after:today',
            'revisions' => 'required|integer|min:0|max:20',
            'cancellation_terms' => 'nullable|string|max:2000',
            'milestones' => 'nullable|array|max:12',
            'milestones.*.title' => 'required_with:milestones|string|max:160',
            'milestones.*.due_date' => 'nullable|date',
            'milestones.*.amount' => 'nullable|numeric|min:0',
        ]);

        $service->propose($application, $request->user(), [
            'title' => $d['title'],
            'scope' => $d['scope'],
            'deliverables' => $d['deliverables'],
            'amount_minor' => Money::toMinor($d['amount']),
            'currency' => $d['currency'],
            'deadline' => $d['deadline'],
            'revisions' => (int) $d['revisions'],
            'cancellation_terms' => $d['cancellation_terms'] ?? null,
            'milestones' => collect($d['milestones'] ?? [])->filter(fn ($m) => filled($m['title'] ?? null))->map(fn ($m) => [
                'title' => $m['title'], 'due_date' => $m['due_date'] ?? null, 'amount_minor' => Money::toMinor($m['amount'] ?? null),
            ])->values()->all() ?: null,
        ]);

        return back()->with('status', 'Agreement version created. Both parties must confirm this exact version.');
    }

    public function confirmAgreement(Request $request, AgreementVersion $agreement, AgreementService $service)
    {
        $data = $request->validate(['content_hash' => 'required|string|size:64', 'acknowledge' => 'accepted']);
        $project = $service->confirm($agreement, $request->user(), $data['content_hash'], $request->ip());

        return $project
            ? redirect()->route('projects.show', $project)->with('status', 'Agreement confirmed by both parties. Your project workspace is ready.')
            : back()->with('status', 'Your confirmation was recorded. Waiting for the other party.');
    }

    private function guardCanApply(Request $request, JobPost $job): void
    {
        abort_unless($job->isLive(), 404);
        if ($job->ownedBy($request->user())) {
            abort(403, 'You cannot apply to your own job.');
        }
        if (! $job->isAcceptingApplications()) {
            abort(403, 'This job is no longer accepting applications.');
        }
        $existing = $job->applications()->where('freelancer_profile_id', $request->user()->freelancerProfile->id)->first();
        if ($existing) {
            abort(redirect()->route('applications.show', $existing)->with('status', 'You have already applied to this job.'));
        }
    }
}
