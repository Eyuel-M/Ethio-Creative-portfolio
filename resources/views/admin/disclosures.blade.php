<x-layouts.admin title="Payment-detail log">
<x-admin.header title="Payment-detail log" subtitle="Freelancers' CBE/Telebirr details are the most sensitive data on the platform. They only unlock after both parties confirm the same agreement — this log shows every time they were opened."/>
<x-admin.panel :pad="false">
    <div class="overflow-x-auto"><table class="w-full min-w-[720px] text-sm">
        <thead class="bg-zinc-50 text-left text-xs font-medium uppercase tracking-wider text-muted"><tr><th class="px-5 py-3">When</th><th class="px-3 py-3">Opened by</th><th class="px-3 py-3">Project</th><th class="px-3 py-3">Freelancer</th></tr></thead>
        <tbody class="divide-y divide-line">
            @forelse($logs as $l)
                @php $p = $projects[$l->target_id] ?? null; @endphp
                <tr class="hover:bg-zinc-50">
                    <td class="px-5 py-3 text-ink-soft">{{ $l->created_at->format('M j, Y H:i') }}</td>
                    <td class="px-3 py-3">@if($l->actor)<a href="{{ route('admin.users.show', $l->actor) }}" class="font-medium hover:underline">{{ $l->actor->name }}</a>@else — @endif</td>
                    <td class="px-3 py-3">@if($p)<a href="{{ route('admin.agreements.show', $p) }}" class="hover:underline">{{ $p->title }}</a>@else <span class="text-muted">deleted</span>@endif</td>
                    <td class="px-3 py-3">{{ $p?->freelancer?->name ?? '—' }}</td>
                </tr>
            @empty
                <tr><td colspan="4" class="px-5 py-10 text-center text-muted">No payment details have been revealed yet.</td></tr>
            @endforelse
        </tbody>
    </table></div>
</x-admin.panel>
{{ $logs->links() }}
</x-layouts.admin>
