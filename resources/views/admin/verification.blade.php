<x-layouts.admin title="Verification">
<x-admin.header title="Verification requests" subtitle="Clients ask to be verified from their Settings. Check the business is real, then approve — a verified badge appears on their jobs."/>
<x-admin.panel :pad="false">
    <x-admin.tabs param="status" :active="$tab" :items="['pending' => ['Pending', $counts['pending'] ?? 0], 'approved' => ['Approved', $counts['approved'] ?? 0], 'rejected' => ['Rejected', $counts['rejected'] ?? 0]]"/>
    <ul class="divide-y divide-line">
        @forelse($requests as $v)
            <li class="grid gap-4 px-5 py-4 lg:grid-cols-[minmax(0,1fr)_auto]">
                <div class="min-w-0">
                    <p class="font-medium"><a href="{{ route('admin.users.show', $v->user) }}" class="hover:underline">{{ $v->business_name ?: $v->user->clientProfile?->displayName() }}</a> <span class="text-sm font-normal text-muted">· {{ $v->user->name }} · {{ $v->user->email }}</span></p>
                    <dl class="mt-2 grid gap-x-6 gap-y-1 text-sm sm:grid-cols-2">
                        <div><dt class="inline text-muted">Registration / TIN:</dt> <dd class="inline font-medium">{{ $v->registration_number ?: '—' }}</dd></div>
                        <div><dt class="inline text-muted">Website:</dt> <dd class="inline font-medium">@if($v->website)<a href="{{ $v->website }}" target="_blank" rel="noopener nofollow" class="text-brand hover:underline">{{ $v->website }}</a>@else — @endif</dd></div>
                        <div><dt class="inline text-muted">Jobs posted:</dt> <dd class="inline font-medium">{{ $v->user->clientProfile?->jobPosts()->count() ?? 0 }}</dd></div>
                        <div><dt class="inline text-muted">Member since:</dt> <dd class="inline font-medium">{{ $v->user->created_at->format('M Y') }}</dd></div>
                    </dl>
                    @if($v->message)<p class="mt-2 rounded-xl bg-zinc-50 px-3 py-2 text-sm text-ink-soft">“{{ $v->message }}”</p>@endif
                    <p class="mt-2 text-xs text-muted">Requested {{ $v->created_at->diffForHumans() }}@if($v->reviewer) · decided by {{ $v->reviewer->name }} {{ $v->reviewed_at->diffForHumans() }}: “{{ $v->decision_note }}”@endif</p>
                </div>
                @if($v->status === 'pending')
                    <div class="flex flex-wrap items-start gap-2">
                        <x-admin.action :action="route('admin.verification.decide', $v)" :fields="['decision' => 'approved']" label="Approve" tone="dark" placeholder="What did you check?"/>
                        <x-admin.action :action="route('admin.verification.decide', $v)" :fields="['decision' => 'rejected']" label="Reject" tone="danger" placeholder="What's missing? (sent to the client)"/>
                    </div>
                @else
                    <x-status :status="$v->status"/>
                @endif
            </li>
        @empty
            <li class="px-5 py-12 text-center text-sm text-muted">No requests here.</li>
        @endforelse
    </ul>
</x-admin.panel>
{{ $requests->links() }}
</x-layouts.admin>
