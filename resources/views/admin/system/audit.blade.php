<x-layouts.admin title="Audit log">
<x-admin.header title="Audit log" subtitle="Every admin action and sensitive event, with who, when and why. Entries can't be edited."/>
<x-admin.panel :pad="false">
    <form class="flex flex-wrap items-center gap-2 border-b border-line p-3 sm:px-5">
        <div class="w-48"><select name="action" class="input h-10" onchange="this.form.submit()"><option value="">All areas</option>@foreach($actions as $g => $n)<option value="{{ $g }}" @selected(request('action') === $g)>{{ ucfirst(str_replace('_', ' ', $g)) }} ({{ $n }})</option>@endforeach</select></div>
        <div class="w-48"><select name="actor" class="input h-10" onchange="this.form.submit()"><option value="">Anyone</option>@foreach($actors as $a)<option value="{{ $a->id }}" @selected((string) request('actor') === (string) $a->id)>{{ $a->name }}</option>@endforeach</select></div>
    </form>
    <div class="overflow-x-auto"><table class="w-full min-w-[820px] text-sm">
        <thead class="bg-zinc-50 text-left text-xs font-medium uppercase tracking-wider text-muted"><tr><th class="px-5 py-3">When</th><th class="px-3 py-3">Who</th><th class="px-3 py-3">What</th><th class="px-3 py-3">Target</th><th class="px-3 py-3">Reason</th></tr></thead>
        <tbody class="divide-y divide-line">
            @forelse($logs as $l)
                <tr class="align-top hover:bg-zinc-50">
                    <td class="whitespace-nowrap px-5 py-3 text-ink-soft">{{ $l->created_at->format('M j, H:i') }}</td>
                    <td class="px-3 py-3">{{ $l->actor?->name ?? 'System' }}</td>
                    <td class="px-3 py-3">{{ \App\Support\Labels::action($l->action) }}<span class="block tabular-nums text-[11px] text-faint">{{ $l->action }}</span></td>
                    <td class="px-3 py-3 text-xs text-muted">@if($l->target_type === 'User' && auth()->user()->canAdmin('users'))<a href="{{ route('admin.users.show', $l->target_id) }}" class="hover:underline">Member #{{ $l->target_id }}</a>@elseif($l->target_type){{ $l->target_type }} {{ \Illuminate\Support\Str::limit($l->target_id, 14) }}@else — @endif</td>
                    <td class="max-w-xs px-3 py-3 text-ink-soft">{{ $l->reason }}</td>
                </tr>
            @empty
                <tr><td colspan="5" class="px-5 py-10 text-center text-muted">Nothing logged.</td></tr>
            @endforelse
        </tbody>
    </table></div>
</x-admin.panel>
{{ $logs->links() }}
</x-layouts.admin>
