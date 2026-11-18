<x-layouts.app title="Order">
<section class="card mx-auto w-full max-w-xl p-8 text-center">
    @if($order->status === 'paid')
        <span class="mx-auto grid size-14 place-items-center rounded-full bg-leaf-soft text-leaf-dark"><x-icon name="check-circle" class="size-8"/></span>
        <h1 class="mt-5 font-display text-3xl font-bold">Payment confirmed</h1>
        <p class="mt-2 text-muted">The provider verified your payment. {{ $order->product->name }} is active{{ $order->entitlement ? ' until '.$order->entitlement->ends_at->format('M j, Y') : '' }}.</p>
    @elseif($order->status === 'failed')
        <span class="mx-auto grid size-14 place-items-center rounded-full bg-rose-50 text-rose-600"><x-icon name="x-circle" class="size-8"/></span>
        <h1 class="mt-5 font-display text-3xl font-bold">Payment failed</h1>
        <p class="mt-2 text-muted">Nothing was activated. You can try again.</p>
    @else
        <span class="mx-auto grid size-14 place-items-center rounded-full bg-amber-50 text-amber-600"><x-icon name="clock" class="size-8"/></span>
        <h1 class="mt-5 font-display text-3xl font-bold">Waiting for confirmation</h1>
        <p class="mt-2 text-muted">We activate your purchase only after the payment provider confirms it server-to-server. Refresh in a moment.</p>
    @endif
    <dl class="mt-6 space-y-2 rounded-2xl bg-zinc-50 p-4 text-left text-sm">
        <div class="flex justify-between"><dt class="text-muted">Service</dt><dd>{{ $order->product->name }}</dd></div>
        @if($order->jobPost)<div class="flex justify-between gap-4"><dt class="text-muted">Job</dt><dd class="truncate">{{ $order->jobPost->title }}</dd></div>@endif
        <div class="flex justify-between"><dt class="text-muted">Amount</dt><dd>{{ \App\Support\Money::format($order->amount_minor, $order->currency) }}</dd></div>
        <div class="flex justify-between"><dt class="text-muted">Reference</dt><dd class="tabular-nums tracking-wide text-xs">{{ $order->provider_reference }}</dd></div>
    </dl>
    <div class="mt-6 flex justify-center gap-2">
        @if($order->jobPost && $order->status === 'paid')<a href="{{ route('jobs.show', $order->jobPost) }}" class="btn btn-dark">View live job</a>@endif
        @if($order->jobPost && $order->status === 'failed')<a href="{{ route('client.jobs.publish', $order->jobPost) }}" class="btn btn-dark">Try again</a>@endif
        <a href="{{ route('billing.index') }}" class="btn btn-light">Billing</a>
    </div>
</section>
</x-layouts.app>
