@php use App\Support\Money; @endphp
<x-layouts.admin title="Applications">
<x-admin.header title="Applications" subtitle="Every proposal across the marketplace."/>
<x-admin.panel :pad="false">
    <x-admin.tabs param="status" :active="$tab" :items="['' => ['All', $counts->sum()], 'submitted' => ['New', $counts['submitted'] ?? 0], 'shortlisted' => ['Shortlisted', $counts['shortlisted'] ?? 0], 'discussion' => ['Discussion', $counts['discussion'] ?? 0], 'accepted' => ['Hired', $counts['accepted'] ?? 0], 'rejected' => ['Rejected', $counts['rejected'] ?? 0], 'withdrawn' => ['Withdrawn', $counts['withdrawn'] ?? 0]]"/>
    <div class="overflow-x-auto"><table class="w-full min-w-[760px] text-sm">
        <thead class="bg-zinc-50 text-left text-xs font-medium uppercase tracking-wider text-muted"><tr><th class="px-5 py-3">Freelancer</th><th class="px-3 py-3">Job</th><th class="px-3 py-3 text-right">Price</th><th class="px-3 py-3 text-right">Days</th><th class="px-3 py-3">Status</th><th class="px-3 py-3">Sent</th></tr></thead>
        <tbody class="divide-y divide-line">
            @forelse($apps as $a)
                <tr class="hover:bg-zinc-50">
                    <td class="px-5 py-3"><a href="{{ route('admin.users.show', $a->freelancer->user) }}" class="font-medium hover:underline">{{ $a->freelancer->user->name }}</a></td>
                    <td class="max-w-xs truncate px-3 py-3"><a href="{{ route('jobs.show', $a->jobPost) }}" target="_blank" class="hover:underline">{{ $a->jobPost->title }}</a><span class="block text-xs text-muted">{{ $a->jobPost->client?->displayName() }}</span></td>
                    <td class="px-3 py-3 text-right tabular-nums">{{ Money::format($a->proposed_amount_minor, $a->currency) }}</td>
                    <td class="px-3 py-3 text-right tabular-nums">{{ $a->estimated_days }}</td>
                    <td class="px-3 py-3"><x-status :status="$a->status"/></td>
                    <td class="px-3 py-3 text-ink-soft">{{ $a->created_at->diffForHumans() }}</td>
                </tr>
            @empty
                <tr><td colspan="6" class="px-5 py-10 text-center text-muted">No applications.</td></tr>
            @endforelse
        </tbody>
    </table></div>
</x-admin.panel>
{{ $apps->links() }}
</x-layouts.admin>
