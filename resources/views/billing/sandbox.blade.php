<x-layouts.app title="Sandbox checkout">
<section class="card mx-auto w-full max-w-md overflow-hidden">
    <div class="bg-[repeating-linear-gradient(45deg,#fef3c7,#fef3c7_10px,#fde68a_10px,#fde68a_20px)] px-6 py-2 text-center text-xs font-semibold text-amber-900">SANDBOX PAYMENT PROVIDER — no real money</div>
    <div class="p-8">
        <p class="text-sm text-muted">Pay {{ config('marketplace.brand') }}</p>
        <p class="mt-1 font-display text-4xl font-bold">{{ \App\Support\Money::format($order->amount_minor, $order->currency) }}</p>
        <p class="mt-2 text-sm">{{ $order->product->name }}@if($order->jobPost) · {{ $order->jobPost->title }}@endif</p>
        <p class="mt-1 tabular-nums tracking-wide text-xs text-muted">{{ $order->provider_reference }}</p>
        @if($order->status === 'pending_provider')
            <form method="POST" action="{{ route('sandbox.complete', $order) }}" class="mt-8 space-y-2">@csrf
                <button name="outcome" value="success" class="btn btn-dark h-12 w-full">Simulate successful payment</button>
                <button name="outcome" value="fail" class="btn btn-light w-full">Simulate failure</button>
            </form>
        @else
            <p class="mt-6"><x-status :status="$order->status"/></p>
            <a href="{{ route('billing.order', $order) }}" class="btn btn-light mt-4 w-full">View order</a>
        @endif
    </div>
</section>
</x-layouts.app>
