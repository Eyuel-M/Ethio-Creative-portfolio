<x-layouts.admin title="Data requests">
<x-admin.header title="Data requests" subtitle="Members asking for a copy of their data or for their account to be deleted (Personal Data Protection Proclamation 1321/2024). Aim to answer within 30 days."/>
<x-admin.panel :pad="false">
    <x-admin.tabs param="status" :active="$tab" :items="['pending' => ['Pending', $counts['pending'] ?? 0], 'completed' => ['Completed', $counts['completed'] ?? 0], 'rejected' => ['Rejected', $counts['rejected'] ?? 0]]"/>
    <ul class="divide-y divide-line">
        @forelse($requests as $r)
            <li class="flex flex-wrap items-start gap-4 px-5 py-4">
                <span class="grid size-10 shrink-0 place-items-center rounded-xl {{ $r->type === 'deletion' ? 'bg-rose-50 text-rose-700' : 'bg-zinc-100 text-ink-soft' }}"><x-icon :name="$r->type === 'deletion' ? 'trash' : 'download'" class="size-5"/></span>
                <div class="min-w-0 flex-1">
                    <p class="font-medium">{{ $r->type === 'deletion' ? 'Delete account' : 'Copy of data' }} — @if($r->user)<a href="{{ route('admin.users.show', $r->user) }}" class="hover:underline">{{ $r->user->name }}</a>@else{{ $r->requester_email }}@endif</p>
                    <p class="text-xs text-muted">{{ $r->requester_email }} · requested {{ $r->created_at->diffForHumans() }} · due {{ $r->created_at->addDays(30)->format('M j') }}@if($r->handler) · handled by {{ $r->handler->name }}: “{{ $r->decision_note }}”@endif</p>
                    @if($r->note)<p class="mt-1.5 rounded-xl bg-zinc-50 px-3 py-2 text-sm text-ink-soft">“{{ $r->note }}”</p>@endif
                </div>
                @if($r->status === 'pending')
                    <div class="flex flex-wrap gap-2">
                        @if($r->type === 'export')
                            <x-admin.action :action="route('admin.data-requests.process', $r)" :fields="['decision' => 'complete']" label="Generate & send" tone="dark" placeholder="Note (e.g. identity confirmed by email)"/>
                        @else
                            <x-admin.action :action="route('admin.data-requests.process', $r)" :fields="['decision' => 'complete']" label="Delete account" tone="danger" confirm="Removes their profile, portfolio, images and personal details. This can't be undone." placeholder="How did you confirm it's really them?"/>
                        @endif
                        <x-admin.action :action="route('admin.data-requests.process', $r)" :fields="['decision' => 'reject']" label="Decline" placeholder="Why? (sent to the member)"/>
                    </div>
                @else
                    <x-status :status="$r->status"/>
                @endif
            </li>
        @empty
            <li class="px-5 py-12 text-center text-sm text-muted">No requests here.</li>
        @endforelse
    </ul>
</x-admin.panel>
{{ $requests->links() }}
</x-layouts.admin>
