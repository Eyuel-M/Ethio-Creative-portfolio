<x-layouts.admin title="Reports">
<x-admin.header title="Reports" subtitle="Things members flagged. Oldest first — take one, check it, then action or dismiss with a reason."/>
<x-admin.panel :pad="false">
    <x-admin.tabs param="status" :active="$tab" :items="['open' => ['Open', $counts['open'] ?? 0], 'mine' => ['Assigned to me', $mine], 'actioned' => ['Actioned', $counts['actioned'] ?? 0], 'dismissed' => ['Dismissed', $counts['dismissed'] ?? 0]]"/>
    <ul class="divide-y divide-line">
        @forelse($reports as $r)
            @php $t = $r->target(); @endphp
            <li class="grid gap-4 px-5 py-4 lg:grid-cols-[minmax(0,1fr)_auto]">
                <div class="min-w-0">
                    <div class="flex flex-wrap items-center gap-2 text-xs">
                        <span class="rounded-full bg-rose-50 px-2 py-0.5 font-semibold text-rose-700">{{ ucfirst($r->reason) }}</span>
                        <span class="text-muted">{{ $t['kind'] ?? ucfirst($r->reportable_type) }} · reported {{ $r->created_at->diffForHumans() }} by {{ $r->reporter?->name ?? 'deleted member' }}</span>
                        @if($r->assignee)<span class="chip">Taken by {{ $r->assignee->id === auth()->id() ? 'you' : $r->assignee->name }}</span>@endif
                    </div>
                    <p class="mt-1.5 font-medium">@if($t && $t['url'])<a href="{{ $t['url'] }}" target="_blank" class="hover:underline">{{ $t['title'] }}</a>@else{{ $t['title'] ?? 'Content no longer exists' }}@endif</p>
                    @if($t && $t['owner'])<p class="text-xs text-muted">Owner: @if(auth()->user()->canAdmin('users'))<a href="{{ route('admin.users.show', $t['owner']) }}" class="font-medium text-ink hover:underline">{{ $t['owner']->name }}</a>@else{{ $t['owner']->name }}@endif · <x-status :status="$t['owner']->status"/></p>@endif
                    @if($r->details)<p class="mt-2 rounded-xl bg-zinc-50 px-3 py-2 text-sm text-ink-soft">“{{ $r->details }}”</p>@endif
                    @if($r->resolution_note)<p class="mt-2 text-xs text-muted">Resolution: {{ $r->resolution_note }} · {{ $r->resolved_at?->diffForHumans() }}</p>@endif
                </div>
                @if($r->status === 'open')
                    <div class="flex flex-wrap items-start gap-2 lg:justify-end">
                        <form method="POST" action="{{ route('admin.reports.assign', $r) }}">@csrf<button class="btn btn-sm btn-light">{{ $r->assigned_to === auth()->id() ? 'Unassign' : 'Take it' }}</button></form>
                        @if($t && $t['model'] instanceof \App\Models\JobPost && $t['model']->status === 'published' && auth()->user()->canAdmin('moderation'))
                            <x-admin.action :action="route('admin.jobs.moderate', $t['model'])" :fields="['action' => 'remove']" label="Remove job" tone="danger"/>
                        @elseif($t && $t['model'] instanceof \App\Models\PortfolioProject && $t['model']->status === 'published' && auth()->user()->canAdmin('moderation'))
                            <x-admin.action :action="route('admin.portfolio.moderate', $t['model'])" :fields="['action' => 'remove']" label="Remove project" tone="danger"/>
                        @endif
                        <x-admin.action :action="route('admin.reports.resolve', $r)" :fields="['status' => 'actioned']" label="Mark actioned" tone="dark" placeholder="What did you do?"/>
                        <x-admin.action :action="route('admin.reports.resolve', $r)" :fields="['status' => 'dismissed']" label="Dismiss" placeholder="Why is no action needed?"/>
                    </div>
                @else
                    <div><x-status :status="$r->status"/></div>
                @endif
            </li>
        @empty
            <li class="px-5 py-12 text-center text-sm text-muted">Nothing here. 🎉</li>
        @endforelse
    </ul>
</x-admin.panel>
{{ $reports->links() }}
</x-layouts.admin>
