<?php

namespace App\Services\Payments;

use App\Models\JobPost;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Str;

class OrderService
{
    public function __construct(private PaymentGateway $gateway) {}

    /** Price always comes from trusted server-side product configuration, never from the browser. */
    public function create(User $user, Product $product, ?JobPost $job = null): Order
    {
        abort_unless($product->is_active, 404);

        $order = Order::create([
            'user_id' => $user->id,
            'product_id' => $product->id,
            'job_post_id' => $job?->id,
            'amount_minor' => $product->price_minor,
            'currency' => $product->currency,
            'status' => 'pending_provider',
            'provider' => $this->gateway->name(),
            'idempotency_key' => (string) Str::ulid(),
        ]);
        $order->update(['provider_reference' => 'sbx_'.Str::lower(Str::random(20))]);

        return $order;
    }

    public function checkoutUrl(Order $order): string
    {
        return $this->gateway->startCheckout($order);
    }
}
