<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use App\Models\Transaction;
use App\Service\MidtransGateway;
use App\Service\PaymentProcessor;
use App\Support\Amount;
use App\Support\DemoMode;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Throwable;

class TransactionController extends Controller
{
    public function checkout(Request $request, MidtransGateway $gateway)
    {
        $validated = $request->validate([
            'plan_id' => 'required|integer|exists:plans,id',
            'terms' => 'accepted',
        ]);
        $plan = Plan::findOrFail($validated['plan_id']);
        abort_unless($plan->duration > 0 && $plan->totalAmount() > 0, 422, 'This plan is unavailable.');

        $transaction = Transaction::create([
            'user_id' => $request->user()->id,
            'plan_id' => $plan->id,
            'transaction_number' => 'ORDER-'.Str::uuid(),
            'total_amount' => $plan->totalAmount(),
            'payment_status' => 'pending',
        ]);

        $statusUrl = route('subscribe.success', ['transaction' => $transaction->transaction_number]);
        if (DemoMode::simulatesPayments()) {
            return response()->json([
                'status' => 'success',
                'checkout_url' => route('demo.payment', $transaction->transaction_number),
                'status_url' => $statusUrl,
            ]);
        }

        try {
            $token = $gateway->createCheckout($transaction);
            $transaction->update(['midtrans_snap_token' => $token]);

            return response()->json(['status' => 'success', 'snap_token' => $token, 'status_url' => $statusUrl]);
        } catch (Throwable $e) {
            $transaction->update(['payment_status' => 'failed']);
            Log::warning('Payment checkout could not be initialized.', ['order_id' => $transaction->transaction_number]);

            return response()->json(['status' => 'error', 'message' => 'Payment is temporarily unavailable. Please try again.'], 503);
        }
    }

    public function callback(Request $request, MidtransGateway $gateway, PaymentProcessor $processor)
    {
        // Offline demo outcomes are accepted only through the authenticated demo form.
        abort_if(DemoMode::simulatesPayments(), 404);
        $key = config('midtrans.server_key');
        abort_unless(is_string($key) && trim($key) !== '', 503, 'Payments are not configured.');

        $validated = $request->validate([
            'order_id' => 'required|string|max:255',
            'status_code' => ['required', 'regex:/^\d{3}$/'],
            'gross_amount' => ['required', 'regex:/^\d{1,10}(?:\.\d{1,2})?$/'],
            'signature_key' => ['required', 'string', 'regex:/^[a-f0-9]{128}$/'],
        ]);
        $signature = hash('sha512', $validated['order_id'].$validated['status_code'].$validated['gross_amount'].$key);
        abort_unless(hash_equals($signature, $validated['signature_key']), 403, 'Invalid payment signature.');

        $transaction = Transaction::where('transaction_number', $validated['order_id'])->firstOrFail();
        abort_unless(Amount::minorUnits($validated['gross_amount']) === Amount::minorUnits($transaction->total_amount), 422, 'Payment amount does not match the order.');
        try {
            // Fetch authoritative status: notification fields are not a payment receipt.
            $status = $gateway->status($transaction->transaction_number);
        } catch (Throwable $e) {
            Log::warning('Payment verification is temporarily unavailable.', ['order_id' => $transaction->transaction_number]);

            return response()->json(['status' => 'error', 'message' => 'Payment verification unavailable. Retry this notification.'], 503);
        }

        try {
            $processor->applyStatus($transaction, $status);
        } catch (InvalidArgumentException $e) {
            return response()->json(['status' => 'error', 'message' => 'Payment details do not match the order.'], 422);
        }

        return response()->json(['status' => 'success']);
    }

    public function status(Request $request, Transaction $transaction)
    {
        abort_unless($transaction->user_id === $request->user()->id, 404);

        return response()->json(['payment_status' => $transaction->payment_status]);
    }
}
