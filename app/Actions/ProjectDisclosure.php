<?php

namespace App\Actions;

use App\Models\AuditLog;
use App\Models\FreelancerPaymentDetails;
use App\Models\Project;
use App\Models\User;

/**
 * The ONLY code path that decrypts freelancer CBE/Telebirr details or reveals direct contact details.
 * Requires: project membership + the project's agreement version confirmed by BOTH parties with matching hash.
 * Never sold, never revealed by an accepted invitation, chat or file download.
 */
class ProjectDisclosure
{
    public function isUnlocked(Project $project): bool
    {
        $agreement = $project->agreement()->with('confirmations')->first();
        if (! $agreement || $agreement->status !== 'confirmed') {
            return false;
        }
        $hash = $agreement->computedHash();
        $ok = $agreement->confirmations->filter(fn ($c) => hash_equals($hash, $c->content_hash))->pluck('user_id');

        return $ok->contains($project->client_user_id) && $ok->contains($project->freelancer_user_id);
    }

    public function for(Project $project, User $viewer): ?array
    {
        if (! $project->hasParticipant($viewer) || ! $this->isUnlocked($project)) {
            return null;
        }

        if ($viewer->id === $project->client_user_id) {
            $profile = $project->freelancer->freelancerProfile;
            $pd = $profile?->paymentDetails;
            AuditLog::record('payment_details.revealed', $project, 'Mutually confirmed agreement', ['viewer' => $viewer->id]);

            return [
                'party' => $project->freelancer->name,
                'email' => $profile?->contact_email ?: $project->freelancer->email,
                'phone' => $profile?->contact_phone,
                'cbe' => $pd?->encrypted_cbe_account_number,
                'telebirr' => $pd?->encrypted_telebirr_number,
                'preferred' => $pd?->preferred_method,
                'show_payment' => true,
            ];
        }

        $client = $project->client->clientProfile;

        return [
            'party' => $client?->displayName() ?? $project->client->name,
            'email' => $project->client->email,
            'phone' => $client?->contact_phone,
            'show_payment' => false,
        ];
    }

    public static function maskForAdmin(?string $v): ?string
    {
        return FreelancerPaymentDetails::mask($v);
    }
}
