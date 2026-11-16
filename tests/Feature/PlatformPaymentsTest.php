<?php

namespace Tests\Feature;

use App\Models\Entitlement;
use App\Models\Order;
use App\Services\Payments\SandboxGateway;

class PlatformPaymentsTest extends MarketplaceTestCase
{
    private function signedPost(array $event, ?string $sig = null)
    {
        $raw = json_encode($event);

        return $this->call('POST', route('webhooks.payments', 'sandbox'), [], [], [], ['CONTENT_TYPE' => 'application/json', 'HTTP_X_SIGNATURE' => $sig ?? app(SandboxGateway::class)->sign($raw)], $raw);
    }

    private function startCheckout(): array
    {
        $this->products();
        $c = $this->client();
        $job = $this->job($c, 'draft');
        $this->actingAs($c)->post(route('client.jobs.checkout', $job), ['product' => 'job_post_standard'])->assertRedirect();

        return [$c, $job, Order::firstOrFail()];
    }

    private function event(Order $o, string $id = 'evt_1', array $data = []): array
    {
        return ['id' => $id, 'type' => 'payment.succeeded', 'data' => array_merge(['reference' => $o->provider_reference, 'amount' => $o->amount_minor, 'currency' => $o->currency], $data)];
    }

    public function test_price_comes_from_server_and_job_waits_for_payment(): void
    {
        [, $job, $order] = $this->startCheckout();
        $this->assertSame(150000, $order->amount_minor);
        $this->assertSame('pending_payment', $job->fresh()->status);
    }

    public function test_browser_return_page_does_not_grant_entitlement(): void
    {
        [$c, $job, $order] = $this->startCheckout();
        $this->actingAs($c)->get(route('billing.order', $order))->assertOk()->assertSee('Waiting for confirmation');
        $this->assertSame('pending_payment', $job->fresh()->status);
        $this->assertSame(0, Entitlement::count());
    }

    public function test_forged_webhook_is_rejected(): void
    {
        [, $job, $order] = $this->startCheckout();
        $this->signedPost($this->event($order), 'bad-signature')->assertStatus(401);
        $this->assertSame('pending_payment', $job->fresh()->status);
    }

    public function test_verified_webhook_publishes_job_once_even_if_duplicated(): void
    {
        [, $job, $order] = $this->startCheckout();
        $this->signedPost($this->event($order))->assertOk()->assertJson(['result' => 'granted']);
        $this->signedPost($this->event($order))->assertOk()->assertJson(['result' => 'duplicate']);
        $this->signedPost($this->event($order, 'evt_2'))->assertOk()->assertJson(['result' => 'already_paid']);

        $this->assertSame('published', $job->fresh()->status);
        $this->assertNotNull($job->fresh()->expires_at);
        $this->assertSame(1, Entitlement::count());
    }

    public function test_amount_mismatch_does_not_grant(): void
    {
        [, $job, $order] = $this->startCheckout();
        $this->signedPost($this->event($order, 'evt_x', ['amount' => 1]))->assertJson(['result' => 'mismatch']);
        $this->assertSame(0, Entitlement::count());
        $this->assertSame('pending_payment', $job->fresh()->status);
    }

    public function test_no_contact_unlock_or_project_payment_products_exist(): void
    {
        $this->products();
        $codes = \App\Models\Product::pluck('code')->implode(' ');
        $this->assertDoesNotMatchRegularExpression('/unlock|escrow|commission|contact/i', $codes);
    }
}
