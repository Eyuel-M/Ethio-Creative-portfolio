<?php

namespace App\Services\Payments;

use App\Models\AuditLog;
use App\Models\Entitlement;
use App\Models\Order;
use App\Models\PaymentEvent;
use App\Notifications\MarketplaceNotification;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;

/**
 * Grants entitlements ONLY from a verified provider event. Idempotent: duplicate event IDs are ignored,
 * an order is granted at most one entitlement (unique order_id), and amount/currency must match.
 */
class WebhookProcessor
{
    public function handle(string $provider, array $event): string
    {
        try {
            $record = PaymentEvent::create([
                'provider' => $provider,
                'provider_event_id' => (string) ($event['id'] ?? ''),
                'type' => (string) ($event['type'] ?? 'unknown'),
                'payload' => $event,
            ]);
        } catch (UniqueConstraintViolationException) {
            return 'duplicate';
        }

        $order = Order::where('provider_reference', $event['data']['reference'] ?? null)->first();
        if (! $order) {
            return 'unknown_order';
        }
        $record->update(['order_id' => $order->id]);

        $result = DB::transaction(function () use ($order, $event, $record) {
            $order = Order::whereKey($order->id)->lockForUpdate()->first();

            if ($event['type'] === 'payment.failed') {
                if ($order->status === 'pending_provider') {
                    $order->update(['status' => 'failed']);
                }
                $record->update(['processed_at' => now()]);

                return 'failed';
            }

            if ($event['type'] !== 'payment.succeeded') {
                $record->update(['processed_at' => now()]);

                return 'ignored';
            }

            if ((int) ($event['data']['amount'] ?? -1) !== (int) $order->amount_minor || ($event['data']['currency'] ?? '') !== $order->currency) {
                AuditLog::record('payment.amount_mismatch', $order, null, ['event' => $event['id']]);
                $record->update(['processed_at' => now()]);

                return 'mismatch';
            }

            if ($order->status === 'paid') {
                $record->update(['processed_at' => now()]);

                return 'already_paid';
            }

            $order->update(['status' => 'paid', 'paid_at' => now()]);
            $this->grant($order);
            $record->update(['processed_at' => now()]);

            return 'granted';
        });

        if ($result === 'granted') {
            \App\Support\Analytics::track('order_paid', $order, ['product' => $order->product->code, 'amount' => $order->amount_minor], $order->user_id);
            $order->user->notify(new MarketplaceNotification(
                'Payment confirmed',
                "Your purchase of {$order->product->name} is active.",
                route('billing.index'),
                'check'
            ));
        }

        return $result;
    }

    private function grant(Order $order): void
    {
        $product = $order->product;
        $start = now();
        $end = now()->addDays($product->duration_days);

        Entitlement::create([
            'user_id' => $order->user_id,
            'product_id' => $product->id,
            'order_id' => $order->id,
            'job_post_id' => $order->job_post_id,
            'starts_at' => $start,
            'ends_at' => $end,
        ]);

        switch ($product->code) {
            case 'job_post_standard':
            case 'job_post_featured':
                $job = $order->jobPost;
                if ($job) {
                    if ($job->status !== 'published') {
                        $job->transitionTo('published', $order->user_id, 'Job-posting fee verified');
                        \App\Support\Analytics::track('job_published', $job, ['category' => $job->category_id], $order->user_id);
                    }
                    $job->forceFill([
                        'published_at' => $job->published_at ?? $start,
                        'expires_at' => $end,
                        'is_featured' => $product->code === 'job_post_featured' || $job->is_featured,
                        'featured_until' => $product->code === 'job_post_featured' ? $end : $job->featured_until,
                    ])->save();
                }
                break;
            case 'job_feature_boost':
                $order->jobPost?->forceFill(['is_featured' => true, 'featured_until' => $end])->save();
                break;
            case 'freelancer_professional':
                $order->user->freelancerProfile?->forceFill(['plan' => 'professional'])->save();
                break;
            case 'freelancer_featured':
                $order->user->freelancerProfile?->forceFill(['featured_until' => $end])->save();
                break;
        }
    }
}
