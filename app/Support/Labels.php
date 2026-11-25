<?php

namespace App\Support;

class Labels
{
    public static function status(string $status): string
    {
        return ucfirst(str_replace('_', ' ', $status));
    }

    /** Human wording for an audit-log action key. */
    public static function action(string $action): string
    {
        $map = [
            'user.status_changed' => 'changed a member\'s status', 'user.note_added' => 'added a note on a member', 'user.plan_changed' => 'changed a member\'s plan',
            'user.client_verified' => 'verified a client', 'user.client_unverified' => 'removed a client\'s verification',
            'admin.role_changed' => 'changed an admin role', 'admin.impersonation_started' => 'started viewing the site as a member', 'admin.impersonation_stopped' => 'stopped viewing as a member',
            'job.remove' => 'removed a job', 'job.feature' => 'featured a job', 'job.unfeature' => 'unfeatured a job', 'job.approve' => 'approved a job',
            'portfolio.approve' => 'approved a portfolio project', 'portfolio.remove' => 'removed a portfolio project', 'portfolio.feature' => 'featured a portfolio project', 'portfolio.unfeature' => 'unfeatured a portfolio project', 'portfolio.restore' => 'restored a portfolio project',
            'report.actioned' => 'actioned a report', 'report.dismissed' => 'dismissed a report', 'report.assigned' => 'took a report',
            'verification.approved' => 'approved a verification', 'verification.rejected' => 'rejected a verification',
            'order.refunded' => 'refunded an order', 'product.updated' => 'updated a plan or fee', 'review.hidden' => 'hid a review', 'review.restored' => 'restored a review',
            'category.created' => 'created a category', 'category.updated' => 'updated a category', 'category.deleted' => 'deleted a category', 'skill.created' => 'added a skill', 'skill.deleted' => 'deleted a skill',
            'announcement.updated' => 'updated the announcement', 'post.created' => 'created an Insights post', 'post.updated' => 'edited an Insights post', 'post.published' => 'published an Insights post', 'post.scheduled' => 'scheduled an Insights post', 'post.unpublished' => 'unpublished an Insights post', 'post.archived' => 'archived an Insights post', 'post.featured' => 'pinned an Insights post', 'post.unfeatured' => 'unpinned an Insights post', 'post.duplicated' => 'duplicated an Insights post', 'post.deleted' => 'deleted an Insights post', 'settings.updated' => 'changed platform switches', 'profile.feature' => 'featured a freelancer', 'profile.unfeature' => 'unfeatured a freelancer',
            'data_request.completed' => 'completed a data request', 'data_request.rejected' => 'rejected a data request',
            'admin.login' => 'signed in as an admin', 'admin.verified' => 'confirmed their admin sign-in code',
            'agreement.confirmed' => 'an agreement was confirmed', 'agreement.proposed' => 'an agreement was proposed',
            'payment.amount_mismatch' => 'a payment amount didn\'t match its order',
            'payment_details.revealed' => 'payment details were revealed (agreement confirmed)', 'export.downloaded' => 'downloaded a data export',
        ];

        return $map[$action] ?? str_replace(['.', '_'], [' · ', ' '], $action);
    }

    /** Tailwind classes for a status pill. */
    public static function tone(string $status): string
    {
        return match ($status) {
            'published', 'approved', 'actioned', 'accepted', 'confirmed', 'completed', 'paid', 'active', 'available', 'receipt_confirmed', 'delivered' => 'bg-emerald-50 text-emerald-700 ring-emerald-600/15',
            'scheduled', 'shortlisted', 'discussion', 'negotiation', 'in_progress', 'submitted_for_review', 'submitted', 'viewed', 'awaiting_confirmations' => 'bg-indigo-50 text-indigo-700 ring-indigo-600/15',
            'pending', 'restricted', 'pending_payment', 'pending_provider', 'draft', 'paused', 'revision_requested', 'not_started', 'created', 'busy', 'partial' => 'bg-amber-50 text-amber-700 ring-amber-600/20',
            'rejected', 'deleted', 'refunded', 'withdrawn', 'cancelled', 'failed', 'removed', 'declined', 'expired', 'closed', 'suspended', 'banned', 'cancellation_requested', 'problem_reported', 'unavailable', 'not_provided' => 'bg-rose-50 text-rose-700 ring-rose-600/15',
            default => 'bg-zinc-100 text-zinc-700 ring-zinc-500/15',
        };
    }

    public static function experience(string $level): string
    {
        return ['entry' => 'Entry Level', 'intermediate' => 'Intermediate', 'expert' => 'Expert'][$level] ?? ucfirst($level);
    }
}
