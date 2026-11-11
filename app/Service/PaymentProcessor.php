<?php

namespace App\Service;

use App\Models\Transaction;
use App\Models\User;
use App\Support\Amount;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class PaymentProcessor
{
    public function applyStatus(Transaction $transaction, array $status): void
    {
        if (($status['order_id'] ?? null) !== $transaction->transaction_number
            || ($status['currency'] ?? null) !== 'IDR'
            || Amount::minorUnits($status['gross_amount'] ?? null) !== Amount::minorUnits($transaction->total_amount)) {
            throw new InvalidArgumentException('Payment details do not match the order.');
        }

        $providerId = $status['transaction_id'] ?? null;
        if (! is_string($providerId) || $providerId === '' || strlen($providerId) > 255) {
            throw new InvalidArgumentException('Missing payment identifier.');
        }

        $state = $status['transaction_status'] ?? '';
        $fraud = $status['fraud_status'] ?? null;
        $paid = (string) ($status['status_code'] ?? '') === '200' && (
            ($state === 'settlement' && in_array($fraud, [null, 'accept'], true))
            || ($state === 'capture' && $fraud === 'accept')
        );

        DB::transaction(function () use ($transaction, $providerId, $paid, $state) {
            // Serialize payments for the same account before locking the order.
            $user = User::whereKey($transaction->user_id)->lockForUpdate()->firstOrFail();
            $order = Transaction::whereKey($transaction->id)->lockForUpdate()->firstOrFail();

            // Duplicate or delayed notifications cannot grant access twice or undo payment.
            if ($order->payment_status === 'success') {
                return;
            }

            if (! $paid) {
                $order->update([
                    'payment_status' => match ($state) {
                        'expire' => 'expired',
                        'deny', 'cancel' => 'failed',
                        default => $order->payment_status,
                    },
                    'midtrans_transaction_id' => $providerId,
                ]);

                return;
            }

            $plan = $order->plan;
            if ($plan->duration < 1) {
                throw new InvalidArgumentException('The purchased plan has no valid duration.');
            }

            // A same-plan renewal retains time already paid for.
            $renewal = $user->memberships()->current()->where('plan_id', $plan->id)->orderByDesc('end_date')->first();
            $expires = $renewal?->end_date->copy() ?? now();
            $user->memberships()->where('active', true)->update(['active' => false]);
            $user->memberships()->create([
                'transaction_id' => $order->id,
                'plan_id' => $plan->id,
                'start_date' => now(),
                'end_date' => $expires->addDays($plan->duration),
                'active' => true,
            ]);
            $order->update(['payment_status' => 'success', 'midtrans_transaction_id' => $providerId]);
        });
    }
}
