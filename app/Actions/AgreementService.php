<?php

namespace App\Actions;

use App\Models\AgreementConfirmation;
use App\Models\AgreementVersion;
use App\Models\Application;
use App\Models\AuditLog;
use App\Models\Project;
use App\Models\User;
use App\Notifications\MarketplaceNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AgreementService
{
    /** Creates a new immutable version; any earlier open version is superseded. */
    public function propose(Application $application, User $author, array $terms): AgreementVersion
    {
        abort_unless($application->isParticipant($author), 403);
        if (in_array($application->status, ['accepted', 'rejected', 'withdrawn'], true)) {
            throw ValidationException::withMessages(['agreement' => 'This application is closed.']);
        }

        $version = DB::transaction(function () use ($application, $author, $terms) {
            if ($application->status !== 'negotiation') {
                if (! $application->canTransitionTo('negotiation') && $application->canTransitionTo('discussion')) {
                    $application->transitionTo('discussion', $author->id, 'Agreement drafted');
                }
                $application->transitionTo('negotiation', $author->id, 'Agreement drafted');
            }

            $application->agreements()->where('status', 'awaiting_confirmations')->update(['status' => 'superseded']);
            $next = (int) $application->agreements()->max('version') + 1;

            return $application->agreements()->create($terms + [
                'version' => $next,
                'created_by' => $author->id,
                'status' => 'awaiting_confirmations',
                'content_hash' => AgreementVersion::hashTerms($terms),
            ]);
        });

        $other = $author->id === $application->freelancer->user_id ? $application->jobPost->client->user : $application->freelancer->user;
        $other->notify(new MarketplaceNotification(
            'Agreement awaiting your confirmation',
            "{$author->name} prepared agreement v{$version->version} for “{$application->jobPost->title}”.",
            route('applications.show', $application),
            'file'
        ));

        return $version;
    }

    /**
     * Records one participant's confirmation of an exact version (identified by content hash).
     * When both have confirmed the same version, the private project workspace is created.
     */
    public function confirm(AgreementVersion $version, User $user, string $hash, ?string $ip = null): ?Project
    {
        $application = $version->application;
        abort_unless($application->isParticipant($user), 403);

        return DB::transaction(function () use ($version, $user, $hash, $ip, $application) {
            $version = AgreementVersion::whereKey($version->id)->lockForUpdate()->first();

            if ($version->status !== 'awaiting_confirmations') {
                throw ValidationException::withMessages(['agreement' => 'This agreement version is no longer open for confirmation.']);
            }
            if (! hash_equals($version->content_hash, $hash) || ! hash_equals($version->computedHash(), $hash)) {
                throw ValidationException::withMessages(['agreement' => 'The agreement changed. Review the latest version before confirming.']);
            }
            if ($version->confirmations()->where('user_id', $user->id)->exists()) {
                throw ValidationException::withMessages(['agreement' => 'You have already confirmed this version.']);
            }

            AgreementConfirmation::create([
                'agreement_version_id' => $version->id,
                'user_id' => $user->id,
                'content_hash' => $hash,
                'ip_address' => $ip,
                'confirmed_at' => now(),
            ]);
            AuditLog::record('agreement.confirmed', $version, null, ['version' => $version->version]);

            $clientId = $application->jobPost->client->user_id;
            $freelancerId = $application->freelancer->user_id;
            $confirmedIds = $version->confirmations()->pluck('user_id')->all();

            if (! in_array($clientId, $confirmedIds, true) || ! in_array($freelancerId, $confirmedIds, true)) {
                $other = $user->id === $clientId ? $application->freelancer->user : $application->jobPost->client->user;
                $other->notify(new MarketplaceNotification('Agreement confirmed by the other party', "{$user->name} confirmed agreement v{$version->version}. Your confirmation is needed.", route('applications.show', $application), 'file'));

                return null;
            }

            $version->update(['status' => 'confirmed']);
            \App\Support\Analytics::track('agreement_confirmed', $application, ['amount' => $version->amount_minor]);
            $application->transitionTo('accepted', $user->id, 'Both parties confirmed agreement v'.$version->version);

            $project = Project::forceCreate([
                'status' => 'in_progress',
                'application_id' => $application->id,
                'agreement_version_id' => $version->id,
                'client_user_id' => $clientId,
                'freelancer_user_id' => $freelancerId,
                'title' => $version->title,
            ]);
            foreach ($version->milestones ?? [] as $i => $m) {
                $project->milestones()->forceCreate([
                    'status' => 'not_started',
                    'title' => $m['title'],
                    'due_date' => $m['due_date'] ?? null,
                    'amount_minor' => $m['amount_minor'] ?? null,
                    'sort_order' => $i,
                ]);
            }
            $project->log('agreement_confirmed', "Both parties confirmed agreement v{$version->version}. Workspace created.");
            $application->conversation?->update(['project_id' => $project->id]);

            foreach ([$application->freelancer->user, $application->jobPost->client->user] as $party) {
                $party->notify(new MarketplaceNotification('Project agreement confirmed', "“{$project->title}” is now active. Contact and payment details are available in the project workspace.", route('projects.show', $project), 'check'));
            }

            return $project;
        });
    }
}
