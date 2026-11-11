<?php

use App\Models\Transaction;
use App\Models\User;
use App\Support\DemoMode;
use Database\Seeders\DemoSeeder;
use Illuminate\Support\Facades\Http;
use Tests\Support\PaymentFixtures;

uses(PaymentFixtures::class);

beforeEach(function () {
    config(['demo.enabled' => true, 'demo.simulate_payments' => true, 'midtrans.is_production' => false]);
    Http::preventStrayRequests();
});

it('provides a clearly labelled demo checkout without contacting a provider', function () {
    $user = User::factory()->create();
    $this->actingAs($user)->postJson('/checkout', ['plan_id' => $this->plan()->id, 'terms' => true])->assertOk()->assertJsonStructure(['checkout_url']);
    $order = Transaction::sole();
    $this->get(route('demo.payment', $order->transaction_number))->assertOk()->assertSee('No money will be charged');
    $this->post(route('demo.payment.complete', $order->transaction_number), ['outcome' => 'settlement'])->assertRedirect();
    expect($order->fresh()->payment_status)->toBe('success');
    expect($user->hasMembershipPlan())->toBeTrue();
    $this->post(route('demo.payment.complete', $order->transaction_number), ['outcome' => 'settlement'])->assertRedirect();
    $this->assertDatabaseCount('memberships', 1);
    Http::assertNothingSent();
});

it('simulates cancellation without activating a plan', function () {
    $order = $this->order();
    $this->actingAs($order->user)->post(route('demo.payment.complete', $order->transaction_number), ['outcome' => 'cancel'])->assertRedirect();
    expect($order->fresh()->payment_status)->toBe('failed');
    $this->assertDatabaseCount('memberships', 0);
});

it('does not allow users to complete another users demo payment', function () {
    $order = $this->order();
    $this->actingAs(User::factory()->create());
    $this->get(route('demo.payment', $order->transaction_number))->assertNotFound();
    $this->post(route('demo.payment.complete', $order->transaction_number), ['outcome' => 'settlement'])->assertNotFound();
    $this->assertDatabaseCount('memberships', 0);
});

it('disables demo actions outside an explicit local sandbox', function (string $setting) {
    $order = $this->order();
    match ($setting) {
        'disabled' => config(['demo.enabled' => false]),
        'production-environment' => $this->app->instance('env', 'production'),
        'production-payments' => config(['midtrans.is_production' => true]),
    };
    expect(DemoMode::enabled())->toBeFalse();
    $this->actingAs($order->user)->withSession(['_token' => 'demo-csrf'])->post(route('demo.payment.complete', $order->transaction_number), ['outcome' => 'settlement', '_token' => 'demo-csrf'])->assertNotFound();
    $this->assertDatabaseCount('memberships', 0);
})->with(['disabled', 'production-environment', 'production-payments']);

it('seeds an offline catalog and three account states without duplicate records', function () {
    $this->seed(DemoSeeder::class);
    $this->seed(DemoSeeder::class);
    $this->assertDatabaseCount('users', 3);
    $this->assertDatabaseCount('plans', 3);
    $this->assertDatabaseCount('movies', 8);
    $this->assertDatabaseCount('memberships', 2);
    expect(User::where('email', 'viewer@webflix.example')->first()->hasMembershipPlan())->toBeTrue();
    expect(User::where('email', 'new@webflix.example')->first()->hasMembershipPlan())->toBeFalse();
    expect(User::where('email', 'expired@webflix.example')->first()->hasMembershipPlan())->toBeFalse();
    Http::assertNothingSent();
});

it('refuses demo seeding in production', function () {
    $this->app->instance('env', 'production');
    expect(fn () => (new DemoSeeder)->run())->toThrow(RuntimeException::class);
    $this->assertDatabaseCount('users', 0);
});

it('uses the real sandbox flow and forbids local payment simulation when configured', function () {
    config(['demo.simulate_payments' => false, 'midtrans.server_key' => 'sandbox-test-key']);
    Http::fake(['app.sandbox.midtrans.com/*' => Http::response(['token' => 'sandbox-token'])]);
    $user = User::factory()->create();
    $this->actingAs($user)->postJson('/checkout', ['plan_id' => $this->plan()->id, 'terms' => true])
        ->assertOk()->assertJsonPath('snap_token', 'sandbox-token')->assertJsonMissingPath('checkout_url');
    $order = Transaction::sole();
    $this->post(route('demo.payment.complete', $order->transaction_number), ['outcome' => 'settlement'])->assertNotFound();
    $this->assertDatabaseCount('memberships', 0);
    Http::fake(['api.sandbox.midtrans.com/*' => Http::response($this->providerStatus($order))]);
    $this->postJson('/api/transaction/payment/callback', $this->notification($order))->assertOk();
    expect($user->hasMembershipPlan())->toBeTrue();
});
