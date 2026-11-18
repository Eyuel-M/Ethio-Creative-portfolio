<x-layouts.app title="Billing">
<section class="card card-pad"><h1 class="font-display text-3xl font-bold">Billing</h1><p class="mt-1 text-muted">Platform services you've purchased. Project payments between clients and freelancers never pass through here.</p></section>
@if($upgrades->isNotEmpty())
<section class="grid gap-4 md:grid-cols-2">
    @foreach($upgrades as $u)
        <div class="card flex flex-col p-7 {{ $loop->first ? 'shadow-[0_30px_60px_-30px_rgba(12,12,16,.35)]' : 'bg-[#f7f7f8]' }}">
            <p class="font-display text-2xl font-medium text-ink-soft">{{ $u->name }}</p>
            <p class="mt-2 flex items-baseline gap-2"><span class="font-display text-4xl font-bold">{{ $u->priceLabel() }}</span><span class="text-ink-soft">/ {{ $u->duration_days }} days</span></p>
            <p class="text-sm text-muted">{{ $u->tagline }}</p>
            <ul class="mt-6 space-y-3">@foreach($u->benefits as $b)<li class="flex gap-3 text-[15px]"><x-icon name="check" class="size-5 shrink-0 text-emerald-600"/>{{ $b }}</li>@endforeach</ul>
            <form method="POST" action="{{ route('billing.purchase', $u) }}" class="mt-auto pt-8">@csrf<button class="btn h-12 w-full {{ $loop->first ? 'btn-dark' : 'btn-light' }}">Purchase</button></form>
        </div>
    @endforeach
</section>
@endif
@if($entitlements->isNotEmpty())
<section class="card card-pad">
    <h2 class="panel-title">Active benefits</h2>
    <ul class="mt-4 grid gap-3 md:grid-cols-2">@foreach($entitlements as $e)<li class="flex items-center justify-between rounded-xl border border-line p-4"><span class="font-medium">{{ $e->product->name }}</span><span class="text-sm text-muted">until {{ $e->ends_at->format('M j, Y') }}</span></li>@endforeach</ul>
</section>
@endif
<section class="card overflow-x-auto">
    <div class="p-5 sm:p-6"><h2 class="panel-title">Order history</h2></div>
    <table class="w-full min-w-[640px] text-sm">
        <thead class="border-y border-line bg-zinc-50/70"><tr><th class="table-head px-6 py-3">Service</th><th class="table-head px-4 py-3">Reference</th><th class="table-head px-4 py-3">Amount</th><th class="table-head px-4 py-3">Status</th><th class="table-head px-4 py-3">Date</th></tr></thead>
        <tbody class="divide-y divide-line">
            @forelse($orders as $o)
                <tr><td class="px-6 py-3"><a href="{{ route('billing.order', $o) }}" class="font-medium hover:underline">{{ $o->product->name }}</a>@if($o->jobPost)<p class="text-xs text-muted">{{ $o->jobPost->title }}</p>@endif</td><td class="px-4 py-3 tabular-nums tracking-wide text-xs text-muted">{{ $o->provider_reference }}</td><td class="px-4 py-3">{{ \App\Support\Money::format($o->amount_minor, $o->currency) }}</td><td class="px-4 py-3"><x-status :status="$o->status"/></td><td class="px-4 py-3 text-muted">{{ $o->created_at->format('M j, Y') }}</td></tr>
            @empty
                <tr><td colspan="5" class="px-6 py-8 text-center text-muted">No purchases yet.</td></tr>
            @endforelse
        </tbody>
    </table>
    <div class="p-4">{{ $orders->links() }}</div>
</section>
</x-layouts.app>
