<?php

namespace App\Services\Payments;

use App\Models\Order;

/**
 * Local development stand-in for a real provider (e.g. Chapa / Telebirr merchant / Stripe —
 * provider choice is an open owner decision). It mimics a hosted checkout and a signed webhook.
 */
class SandboxGateway implements PaymentGateway
{
    public function name(): string
    {
        return 'sandbox';
    }

    public function startCheckout(Order $order): string
    {
        return route('sandbox.checkout', $order);
    }

    public function sign(string $rawBody): string
    {
        return hash_hmac('sha256', $rawBody, (string) config('marketplace.payments.webhook_secret'));
    }

    public function verifySignature(string $rawBody, ?string $signature): bool
    {
        return $signature !== null && hash_equals($this->sign($rawBody), $signature);
    }
}
