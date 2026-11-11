<?php

use App\Models\Transaction;
use App\Models\User;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\Support\PaymentFixtures;

uses(PaymentFixtures::class);

beforeEach(function () {
    config(['midtrans.server_key' => 'sandbox-test-key', 'midtrans.is_production' => false, 'demo.enabled' => false]);
    Http::preventStrayRequests();
});

it('requires authentication for both checkout endpoints', function (string $url) {
    $this->postJson($url, ['plan_id' => 1, 'amount' => 1, 'terms' => true])->assertUnauthorized();
    $this->assertDatabaseCount('transactions', 0);
})->with(['/checkout', '/subscribe/checkout']);

it('uses the plan price and tax instead of browser values', function (string $url) {
    $user = User::factory()->create();
    $other = User::factory()->create();
    $plan = $this->plan();
    Http::fake(['app.sandbox.midtrans.com/*' => Http::response(['token' => 'sandbox-token'])]);

    $this->actingAs($user)->postJson($url, [
        'plan_id' => $plan->id, 'amount' => 1, 'user_id' => $other->id, 'terms' => true,
    ])->assertOk()->assertJsonPath('snap_token', 'sandbox-token');

    $order = Transaction::sole();
    expect((float) $order->total_amount)->toBe(55999.0);
    expect($order->user_id)->toBe($user->id);
    expect($order->midtrans_snap_token)->toBe('sandbox-token');
    expect($order->payment_status)->toBe('pending');
    $this->assertDatabaseCount('memberships', 0);
    Http::assertSent(fn (Request $request) => $request->url() === 'https://app.sandbox.midtrans.com/snap/v1/transactions'
        && $request['transaction_details']['gross_amount'] === 55999
        && array_sum(array_column($request['item_details'], 'price')) === 55999
        && $request->hasHeader('Authorization', 'Basic '.base64_encode('sandbox-test-key:')));
})->with(['/checkout', '/subscribe/checkout']);

it('validates the plan and acknowledgement before contacting the provider', function (array $attributes, string $field) {
    $plan = $this->plan();
    $body = array_merge(['plan_id' => $plan->id, 'terms' => true], $attributes);
    $this->actingAs(User::factory()->create())->postJson('/checkout', $body)->assertUnprocessable()->assertJsonValidationErrors($field);
    Http::assertNothingSent();
    $this->assertDatabaseCount('transactions', 0);
})->with([[['plan_id' => 999], 'plan_id'], [['terms' => false], 'terms'], [['plan_id' => null], 'plan_id']]);

it('creates distinct order IDs for consecutive checkouts', function () {
    Http::fake(['app.sandbox.midtrans.com/*' => Http::response(['token' => 'token'])]);
    $this->actingAs(User::factory()->create());
    $body = ['plan_id' => $this->plan()->id, 'terms' => true];
    $this->postJson('/checkout', $body)->assertOk();
    $this->postJson('/checkout', $body)->assertOk();
    expect(Transaction::pluck('transaction_number')->unique())->toHaveCount(2);
});

it('does not leak provider errors or grant membership on checkout failure', function () {
    Http::fake(['app.sandbox.midtrans.com/*' => Http::response(['error_messages' => ['private provider details']], 500)]);
    $this->actingAs(User::factory()->create())->postJson('/checkout', ['plan_id' => $this->plan()->id, 'terms' => true])
        ->assertStatus(503)->assertDontSee('private provider details');
    expect(Transaction::sole()->payment_status)->toBe('failed');
    $this->assertDatabaseCount('memberships', 0);
});

it('fails closed if payment credentials are missing', function () {
    config(['midtrans.server_key' => '']);
    $this->actingAs(User::factory()->create())->postJson('/checkout', ['plan_id' => $this->plan()->id, 'terms' => true])->assertStatus(503);
    Http::assertNothingSent();
    $this->assertDatabaseCount('memberships', 0);
});

it('selects the production endpoint only when configured', function () {
    config(['midtrans.is_production' => true]);
    Http::fake(['app.midtrans.com/*' => Http::response(['token' => 'token'])]);
    $this->actingAs(User::factory()->create())->postJson('/checkout', ['plan_id' => $this->plan()->id, 'terms' => true])->assertOk();
    Http::assertSent(fn (Request $request) => str_starts_with($request->url(), 'https://app.midtrans.com/'));
});

it('does not render a success receipt without a paid order', function () {
    $order = $this->order();
    $this->actingAs($order->user)->get('/subscribe/success')->assertRedirect(route('subscribe.plans'));
    $this->get(route('subscribe.success', ['transaction' => $order->transaction_number]))
        ->assertOk()->assertSee('Waiting for payment verification')->assertDontSee('Your membership is ready');
});

it('hides other users payment receipts and status', function () {
    $order = $this->order();
    $this->actingAs(User::factory()->create());
    $this->get(route('subscribe.success', ['transaction' => $order->transaction_number]))->assertNotFound();
    $this->getJson(route('transactions.status', $order->transaction_number))->assertNotFound();
});

it('requires CSRF tokens for browser checkout and demo payment forms', function (string $endpoint) {
    config(['demo.enabled' => true, 'demo.simulate_payments' => true]);
    $order = $this->order();
    $this->app->bind(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class, fn () => new class($this->app, $this->app['encrypter']) extends \Illuminate\Foundation\Http\Middleware\ValidateCsrfToken
    {
        protected function runningUnitTests(): bool
        {
            return false;
        }
    });
    $url = $endpoint === 'demo' ? route('demo.payment.complete', $order->transaction_number) : '/'.$endpoint;
    $this->actingAs($order->user)->withSession(['_token' => 'session-token'])->post($url, [
        'plan_id' => $order->plan_id, 'terms' => true, 'outcome' => 'settlement',
    ])->assertStatus(419);
    $this->assertDatabaseCount('memberships', 0);
    Http::assertNothingSent();
})->with(['checkout', 'subscribe/checkout', 'demo']);
