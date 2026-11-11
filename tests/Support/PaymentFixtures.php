<?php

namespace Tests\Support;

use App\Models\Membership;
use App\Models\Movie;
use App\Models\Plan;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Support\Str;

trait PaymentFixtures
{
    protected function plan(array $attributes = []): Plan
    {
        return Plan::create(array_merge([
            'title' => 'Basic', 'price' => '49999.00', 'duration' => 30,
            'resolution' => '720p', 'max_devices' => 1,
        ], $attributes));
    }

    protected function order(?User $user = null, ?Plan $plan = null): Transaction
    {
        $user ??= User::factory()->create();
        $plan ??= $this->plan();

        return Transaction::create([
            'user_id' => $user->id, 'plan_id' => $plan->id,
            'transaction_number' => 'ORDER-'.Str::uuid(),
            'total_amount' => $plan->totalAmount(), 'payment_status' => 'pending',
        ]);
    }

    protected function providerStatus(Transaction $order, array $attributes = []): array
    {
        return array_merge([
            'order_id' => $order->transaction_number,
            'gross_amount' => number_format($order->total_amount, 2, '.', ''),
            'currency' => 'IDR', 'status_code' => '200',
            'transaction_status' => 'settlement', 'fraud_status' => 'accept',
            'transaction_id' => 'provider-'.$order->id,
        ], $attributes);
    }

    protected function notification(Transaction $order, array $attributes = []): array
    {
        $body = $this->providerStatus($order, $attributes);
        $body['signature_key'] = hash('sha512', $body['order_id'].$body['status_code'].$body['gross_amount'].config('midtrans.server_key'));

        return $body;
    }

    protected function membership(User $user, Plan $plan, array $attributes = []): Membership
    {
        return $user->memberships()->create(array_merge([
            'plan_id' => $plan->id, 'start_date' => now()->subDay(),
            'end_date' => now()->addDays(10), 'active' => true,
        ], $attributes));
    }

    protected function movie(): Movie
    {
        return Movie::create([
            'title' => 'Demo film', 'slug' => 'demo-film', 'description' => 'A fictional film.',
            'director' => 'Demo director', 'writers' => 'Demo writer', 'stars' => 'Demo cast',
            'poster' => '/assets/img/Blackhat.png', 'release_date' => '2026-01-01', 'duration' => 90,
            'url_720' => '/demo/player?quality=720p', 'url_1080' => '/demo/player?quality=1080p',
            'url_4k' => '/demo/player?quality=4K',
        ]);
    }
}
