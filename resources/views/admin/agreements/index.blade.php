<x-layouts.admin title="Agreements & projects">
<x-admin.header title="Agreements & projects" subtitle="Every project created from a mutually confirmed agreement. Open one to see each version and both confirmations — the facts for any dispute."/>
<x-admin.panel :pad="false">
    <x-admin.tabs param="status" :active="$tab" :items="['' => ['All', $counts->sum()], 'in_progress' => ['In progress', $counts['in_progress'] ?? 0], 'submitted_for_review' => ['In review', $counts['submitted_for_review'] ?? 0], 'completed' => ['Completed', $counts['completed'] ?? 0], 'cancellation_requested' => ['Cancellation asked', $counts['cancellation_requested'] ?? 0], 'cancelled' => ['Cancelled', $counts['cancelled'] ?? 0]]"/>
    <form class="border-b border-line p-3 sm:px-5"><input type="hidden" name="status" value="{{ $tab }}"><div class="relative max-w-sm"><x-icon name="search" class="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-muted"/><input name="q" value="{{ request('q') }}" class="input h-10 pl-9" placeholder="Title or ID"></div></form>
    <ul class="divide-y divide-line">
        @forelse($projects as $p)
            <li><a href="{{ route('admin.agreements.show', $p) }}" class="flex flex-wrap items-center gap-4 px-5 py-3.5 hover:bg-zinc-50">
                <span class="min-w-0 flex-1"><span class="block truncate font-medium">{{ $p->title }}</span><span class="block text-xs text-muted">{{ $p->client?->name }} (client) ↔ {{ $p->freelancer?->name }} (freelancer) · v{{ $p->agreement?->version }} · {{ $p->agreement ? \App\Support\Money::format($p->agreement->amount_minor, $p->agreement->currency) : '' }}</span></span>
                <span class="text-xs text-muted">{{ $p->created_at->format('M j, Y') }}</span><x-status :status="$p->status"/>
            </a></li>
        @empty
            <li class="px-5 py-12 text-center text-sm text-muted">No projects yet.</li>
        @endforelse
    </ul>
</x-admin.panel>
{{ $projects->links() }}
</x-layouts.admin>
