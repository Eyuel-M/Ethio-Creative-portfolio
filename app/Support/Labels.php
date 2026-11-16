<?php

namespace App\Support;

class Labels
{
    public static function status(string $status): string
    {
        return ucfirst(str_replace('_', ' ', $status));
    }

    /** Tailwind classes for a status pill. */
    public static function tone(string $status): string
    {
        return match ($status) {
            'published', 'accepted', 'confirmed', 'completed', 'paid', 'active', 'available', 'receipt_confirmed', 'delivered' => 'bg-emerald-50 text-emerald-700 ring-emerald-600/15',
            'shortlisted', 'discussion', 'negotiation', 'in_progress', 'submitted_for_review', 'submitted', 'viewed', 'awaiting_confirmations' => 'bg-indigo-50 text-indigo-700 ring-indigo-600/15',
            'pending', 'pending_payment', 'pending_provider', 'draft', 'paused', 'revision_requested', 'not_started', 'created', 'busy', 'partial' => 'bg-amber-50 text-amber-700 ring-amber-600/20',
            'rejected', 'withdrawn', 'cancelled', 'failed', 'removed', 'declined', 'expired', 'closed', 'suspended', 'banned', 'cancellation_requested', 'problem_reported', 'unavailable', 'not_provided' => 'bg-rose-50 text-rose-700 ring-rose-600/15',
            default => 'bg-zinc-100 text-zinc-700 ring-zinc-500/15',
        };
    }

    public static function experience(string $level): string
    {
        return ['entry' => 'Entry Level', 'intermediate' => 'Intermediate', 'expert' => 'Expert'][$level] ?? ucfirst($level);
    }
}
