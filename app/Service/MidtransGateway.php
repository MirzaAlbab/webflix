<?php

namespace App\Service;

use App\Models\Transaction;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class MidtransGateway
{
    public function createCheckout(Transaction $transaction): string
    {
        $plan = $transaction->plan;
        $items = [[
            'id' => (string) $plan->id,
            'price' => $plan->subtotalAmount(),
            'quantity' => 1,
            'name' => $plan->title,
        ]];
        if ($plan->taxAmount() > 0) {
            $items[] = ['id' => 'tax', 'price' => $plan->taxAmount(), 'quantity' => 1, 'name' => 'PPN 12%'];
        }

        $host = config('midtrans.is_production') ? 'https://app.midtrans.com' : 'https://app.sandbox.midtrans.com';
        $token = $this->client()->post($host.'/snap/v1/transactions', [
            'transaction_details' => [
                'order_id' => $transaction->transaction_number,
                'gross_amount' => (int) $transaction->total_amount,
            ],
            'customer_details' => [
                'first_name' => $transaction->user->name,
                'email' => $transaction->user->email,
            ],
            'item_details' => $items,
        ])->throw()->json('token');

        if (! is_string($token) || $token === '') {
            throw new RuntimeException('Payment provider did not return a checkout token.');
        }

        return $token;
    }

    public function status(string $orderId): array
    {
        $host = config('midtrans.is_production') ? 'https://api.midtrans.com' : 'https://api.sandbox.midtrans.com';
        $status = $this->client()->get($host.'/v2/'.rawurlencode($orderId).'/status')->throw()->json();

        if (! is_array($status)) {
            throw new RuntimeException('Invalid payment provider response.');
        }

        return $status;
    }

    private function client(): PendingRequest
    {
        $key = config('midtrans.server_key');
        if (! is_string($key) || trim($key) === '') {
            throw new RuntimeException('Payment provider is not configured.');
        }

        return Http::withBasicAuth($key, '')->acceptJson()->connectTimeout(5)->timeout(20);
    }
}
