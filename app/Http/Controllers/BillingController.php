<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Product;
use App\Services\Payments\OrderService;
use App\Services\Payments\SandboxGateway;
use App\Services\Payments\WebhookProcessor;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class BillingController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $orders = $user->orders()->with(['product', 'jobPost'])->latest()->paginate(15);
        $entitlements = $user->entitlements()->with('product')->where('ends_at', '>', now())->get();
        $upgrades = $user->is_freelancer ? Product::where('audience', 'freelancer')->where('is_active', true)->orderBy('sort_order')->get() : collect();

        return view('billing.index', compact('orders', 'entitlements', 'upgrades'));
    }

    public function purchase(Request $request, Product $product, OrderService $orders)
    {
        abort_unless($product->audience === 'freelancer' && $request->user()->is_freelancer, 403);
        $order = $orders->create($request->user(), $product);
        \App\Support\Analytics::track('checkout_started', $order, ['product' => $product->code]);

        return redirect()->away($orders->checkoutUrl($order));
    }

    /** The return page shows status only; it never grants an entitlement itself. */
    public function order(Request $request, Order $order)
    {
        abort_unless($order->user_id === $request->user()->id, 403);
        $order->load(['product', 'jobPost', 'entitlement']);

        return view('billing.order', compact('order'));
    }

    public function sandbox(Request $request, Order $order)
    {
        abort_unless(config('marketplace.payments.gateway') === 'sandbox', 404);
        abort_unless($order->user_id === $request->user()->id, 403);
        $order->load(['product', 'jobPost']);

        return view('billing.sandbox', compact('order'));
    }

    /**
     * Simulates the provider: it sends a signed server-to-server webhook through the same processor
     * the real webhook endpoint uses, then redirects the browser to the (status-only) return page.
     */
    public function sandboxComplete(Request $request, Order $order, SandboxGateway $gateway, WebhookProcessor $processor)
    {
        abort_unless(config('marketplace.payments.gateway') === 'sandbox', 404);
        abort_unless($order->user_id === $request->user()->id, 403);
        $data = $request->validate(['outcome' => 'required|in:success,fail']);

        $event = [
            'id' => 'evt_'.Str::lower(Str::random(24)),
            'type' => $data['outcome'] === 'success' ? 'payment.succeeded' : 'payment.failed',
            'data' => ['reference' => $order->provider_reference, 'amount' => $order->amount_minor, 'currency' => $order->currency],
        ];
        $raw = json_encode($event);
        abort_unless($gateway->verifySignature($raw, $gateway->sign($raw)), 500);
        $processor->handle($gateway->name(), $event);

        return redirect()->route('billing.order', $order);
    }
}
