<?php

namespace App\Http\Controllers;

use App\Services\Payments\PaymentGateway;
use App\Services\Payments\WebhookProcessor;
use Illuminate\Http\Request;

class PaymentWebhookController extends Controller
{
    public function handle(Request $request, string $provider, PaymentGateway $gateway, WebhookProcessor $processor)
    {
        abort_unless($provider === $gateway->name(), 404);
        $raw = $request->getContent();
        if (! $gateway->verifySignature($raw, $request->header('X-Signature'))) {
            return response()->json(['error' => 'invalid signature'], 401);
        }
        $event = json_decode($raw, true);
        if (! is_array($event) || empty($event['id']) || empty($event['type'])) {
            return response()->json(['error' => 'malformed'], 422);
        }

        return response()->json(['result' => $processor->handle($provider, $event)]);
    }
}
