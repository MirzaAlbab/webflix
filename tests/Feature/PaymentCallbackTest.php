<?php

use App\Models\Membership;
use App\Service\PaymentProcessor;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\Support\PaymentFixtures;

uses(PaymentFixtures::class);

beforeEach(function () {
    $this->travelTo(now()->setDateTime(2026, 11, 11, 12, 0, 0));
    config(['midtrans.server_key' => 'sandbox-test-key', 'midtrans.is_production' => false, 'demo.enabled' => false]);
    Http::preventStrayRequests();
});

it('verifies signatures before accepting notifications', function () {
    $order = $this->order();
    $body = $this->notification($order);
    $body['signature_key'] = str_repeat('0', 128);
    $this->postJson('/api/transaction/payment/callback', $body)->assertForbidden();
    Http::assertNothingSent();
    $this->assertDatabaseCount('memberships', 0);
});

it('rejects missing fields and missing credentials', function () {
    $this->postJson('/api/transaction/payment/callback', [])->assertUnprocessable();
    config(['midtrans.server_key' => '']);
    $this->postJson('/api/transaction/payment/callback', [])->assertStatus(503);
    Http::assertNothingSent();
});

it('rejects notifications for unknown orders', function () {
    $order = $this->order();
    $body = $this->notification($order);
    $order->delete();
    $this->postJson('/api/transaction/payment/callback', $body)->assertNotFound();
    Http::assertNothingSent();
});

it('grants one membership for a verified payment and ignores duplicate delivery', function (string $state) {
    $order = $this->order();
    Http::fake(['api.sandbox.midtrans.com/*' => Http::response($this->providerStatus($order, ['transaction_status' => $state]))]);
    $body = $this->notification($order);
    $this->postJson('/api/transaction/payment/callback', $body)->assertOk();
    $this->postJson('/api/transaction/payment/callback', $body)->assertOk();
    expect($order->fresh()->payment_status)->toBe('success');
    $membership = Membership::sole();
    expect($membership->transaction_id)->toBe($order->id);
    expect($membership->start_date->equalTo(now()))->toBeTrue();
    expect($membership->end_date->equalTo(now()->addDays(30)))->toBeTrue();
    Http::assertSent(fn (Request $request) => $request->url() === 'https://api.sandbox.midtrans.com/v2/'.$order->transaction_number.'/status');
})->with(['settlement', 'capture']);

it('uses provider status instead of a claimed success in the notification', function (array $status) {
    $order = $this->order();
    Http::fake(['api.sandbox.midtrans.com/*' => Http::response($this->providerStatus($order, $status))]);
    $this->postJson('/api/transaction/payment/callback', $this->notification($order))->assertOk();
    $this->assertDatabaseCount('memberships', 0);
    expect($order->fresh()->payment_status)->toBe('pending');
})->with([
    [['transaction_status' => 'pending', 'status_code' => '201']],
    [['transaction_status' => 'capture', 'fraud_status' => 'challenge']],
    [['transaction_status' => 'capture', 'fraud_status' => null]],
    [['transaction_status' => 'settlement', 'fraud_status' => 'deny']],
    [['transaction_status' => 'settlement', 'status_code' => '201']],
]);

it('rejects mismatched provider data', function (array $changes) {
    $order = $this->order();
    Http::fake(['api.sandbox.midtrans.com/*' => Http::response($this->providerStatus($order, $changes))]);
    $this->postJson('/api/transaction/payment/callback', $this->notification($order))->assertUnprocessable();
    $this->assertDatabaseCount('memberships', 0);
    expect($order->fresh()->payment_status)->toBe('pending');
})->with([[['gross_amount' => '1.00']], [['currency' => 'USD']], [['order_id' => 'another-order']], [['transaction_id' => null]]]);

it('rejects a signed notification with the wrong amount', function () {
    $order = $this->order();
    $this->postJson('/api/transaction/payment/callback', $this->notification($order, ['gross_amount' => '1.00']))->assertUnprocessable();
    Http::assertNothingSent();
});

it('keeps a failed verification retryable', function () {
    $order = $this->order();
    Http::fake(['api.sandbox.midtrans.com/*' => Http::response([], 503)]);
    $this->postJson('/api/transaction/payment/callback', $this->notification($order))->assertStatus(503);
    $this->assertDatabaseCount('memberships', 0);
    expect($order->fresh()->payment_status)->toBe('pending');
});

it('records unsuccessful payments without granting access', function (string $state, string $expected) {
    $order = $this->order();
    Http::fake(['api.sandbox.midtrans.com/*' => Http::response($this->providerStatus($order, ['transaction_status' => $state, 'status_code' => '202']))]);
    $this->postJson('/api/transaction/payment/callback', $this->notification($order))->assertOk();
    expect($order->fresh()->payment_status)->toBe($expected);
    $this->assertDatabaseCount('memberships', 0);
})->with([['deny', 'failed'], ['cancel', 'failed'], ['expire', 'expired']]);

it('does not undo success when delayed non-success notifications arrive', function (string $state) {
    $order = $this->order();
    app(PaymentProcessor::class)->applyStatus($order, $this->providerStatus($order));
    Http::fake(['api.sandbox.midtrans.com/*' => Http::response($this->providerStatus($order, ['transaction_status' => $state]))]);
    $this->postJson('/api/transaction/payment/callback', $this->notification($order))->assertOk();
    expect($order->fresh()->payment_status)->toBe('success');
    expect(Membership::sole()->active)->toBeTrue();
})->with(['pending', 'expire', 'cancel']);

it('preserves paid time for same-plan renewal and deactivates superseded entitlements', function () {
    $order = $this->order();
    $previous = $this->membership($order->user, $order->plan);
    app(PaymentProcessor::class)->applyStatus($order, $this->providerStatus($order));
    $current = Membership::where('active', true)->sole();
    expect($current->end_date->equalTo(now()->addDays(40)))->toBeTrue();
    expect($previous->fresh()->active)->toBeFalse();
});

it('verifies the stored checkout amount even if the plan price changes', function () {
    $order = $this->order();
    $order->plan->update(['price' => 99999]);
    Http::fake(['api.sandbox.midtrans.com/*' => Http::response($this->providerStatus($order))]);
    $this->postJson('/api/transaction/payment/callback', $this->notification($order))->assertOk();
    expect($order->fresh()->payment_status)->toBe('success');
});
