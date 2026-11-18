<x-layouts.app title="Orders">
@include('admin._nav')
<section class="card overflow-x-auto">
    <p class="p-5 text-sm text-muted">Platform-service orders only. There is no project-payment ledger.</p>
    <table class="w-full min-w-[800px] text-sm">
        <thead class="border-y border-line bg-zinc-50/70"><tr><th class="table-head px-5 py-3">Order</th><th class="table-head px-4 py-3">User</th><th class="table-head px-4 py-3">Product</th><th class="table-head px-4 py-3">Amount</th><th class="table-head px-4 py-3">Status</th><th class="table-head px-4 py-3">Created</th></tr></thead>
        <tbody class="divide-y divide-line">@foreach($orders as $o)<tr><td class="px-5 py-3 tabular-nums tracking-wide text-xs">{{ $o->provider_reference }}</td><td class="px-4 py-3">{{ $o->user->name }}</td><td class="px-4 py-3">{{ $o->product->name }}</td><td class="px-4 py-3">{{ \App\Support\Money::format($o->amount_minor, $o->currency) }}</td><td class="px-4 py-3"><x-status :status="$o->status"/></td><td class="px-4 py-3 text-muted">{{ $o->created_at->format('M j, H:i') }}</td></tr>@endforeach</tbody>
    </table>
    <div class="p-4">{{ $orders->links() }}</div>
</section>
</x-layouts.app>
