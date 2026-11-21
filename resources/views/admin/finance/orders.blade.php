@php use App\Support\Money; @endphp
<x-layouts.admin title="Orders & revenue">
<x-admin.header title="Orders & revenue" subtitle="Platform services only: job-post fees, featured listings and subscriptions. Project payments never pass through the platform.">
    <x-slot:actions><x-admin.range :days="$days"/><a href="{{ route('admin.orders.export', ['days' => $days]) }}" class="btn btn-sm btn-light"><x-icon name="download" class="size-4"/> CSV</a></x-slot:actions>
</x-admin.header>
<div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
    <x-admin.stat label="Revenue" :value="Money::format($revenue, 'ETB')" :delta="\App\Support\Insights::change($revenue, $prev)" hint="vs previous period"/>
    <x-admin.stat label="Paying members" :value="number_format($payers)"/>
    <x-admin.stat label="Refunded" :value="Money::format($refunded, 'ETB')"/>
    <x-admin.stat label="Failed payments" :value="number_format($failed)" :tone="$failed ? 'alert' : null" :href="route('admin.orders.issues')"/>
</div>
<div class="grid grid-cols-1 gap-5 xl:grid-cols-[minmax(0,1.6fr)_minmax(0,1fr)]">
    <x-admin.panel title="Revenue per day" subtitle="Paid orders, ETB"><x-admin.columns :series="$daily" label="Revenue" format="money"/></x-admin.panel>
    <x-admin.panel title="By service"><x-admin.bars :rows="$byProduct" format="money" empty="No paid orders in this period."/></x-admin.panel>
</div>
<x-admin.panel :pad="false">
    <x-admin.tabs param="status" :active="$tab" :items="['' => ['All', $counts->sum()], 'paid' => ['Paid', $counts['paid'] ?? 0], 'pending_provider' => ['Pending', $counts['pending_provider'] ?? 0], 'failed' => ['Failed', $counts['failed'] ?? 0], 'refunded' => ['Refunded', $counts['refunded'] ?? 0], 'expired' => ['Expired', $counts['expired'] ?? 0]]"/>
    <div class="overflow-x-auto"><table class="w-full min-w-[860px] text-sm">
        <thead class="bg-zinc-50 text-left text-xs font-medium uppercase tracking-wider text-muted"><tr><th class="px-5 py-3">Order</th><th class="px-3 py-3">Member</th><th class="px-3 py-3">Service</th><th class="px-3 py-3 text-right">Amount</th><th class="px-3 py-3">Status</th><th class="px-3 py-3">Provider ref</th><th class="px-3 py-3"></th></tr></thead>
        <tbody class="divide-y divide-line">
            @forelse($orders as $o)
                <tr class="hover:bg-zinc-50">
                    <td class="px-5 py-3"><span class="tabular-nums text-xs">{{ \Illuminate\Support\Str::limit($o->id, 12, '…') }}</span><span class="block text-xs text-muted">{{ $o->created_at->format('M j, Y H:i') }}</span></td>
                    <td class="px-3 py-3">@if($o->user)<a href="{{ route('admin.users.show', $o->user) }}" class="hover:underline">{{ $o->user->name }}</a>@endif</td>
                    <td class="px-3 py-3">{{ $o->product?->name }}@if($o->jobPost)<span class="block max-w-[220px] truncate text-xs text-muted">{{ $o->jobPost->title }}</span>@endif</td>
                    <td class="px-3 py-3 text-right font-medium tabular-nums">{{ Money::format($o->amount_minor, $o->currency) }}</td>
                    <td class="px-3 py-3"><x-status :status="$o->status"/></td>
                    <td class="px-3 py-3 tabular-nums text-xs text-muted">{{ $o->provider }} · {{ $o->provider_reference ?? '—' }}</td>
                    <td class="px-3 py-3 text-right">@if($o->status === 'paid')<x-admin.action :action="route('admin.orders.refund', $o)" label="Refund" tone="danger" confirm="Access ends now. Return the money through the payment provider."/>@endif</td>
                </tr>
            @empty
                <tr><td colspan="7" class="px-5 py-10 text-center text-muted">No orders.</td></tr>
            @endforelse
        </tbody>
    </table></div>
</x-admin.panel>
{{ $orders->links() }}
</x-layouts.admin>
