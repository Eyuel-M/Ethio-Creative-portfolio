<?php

namespace App\Services\Payments;

use App\Models\Order;

/** Adapter for the provider that collects the platform's OWN service fees only. */
interface PaymentGateway
{
    public function name(): string;

    /** Returns the URL the purchaser is redirected to. */
    public function startCheckout(Order $order): string;

    public function verifySignature(string $rawBody, ?string $signature): bool;
}
