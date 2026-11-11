<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Service\PaymentProcessor;
use App\Support\DemoMode;
use Illuminate\Http\Request;

class DemoPaymentController extends Controller
{
    public function show(Request $request, Transaction $transaction)
    {
        $this->authorizeDemo($request, $transaction);

        return view('subscribe.demo-payment', compact('transaction'));
    }

    public function complete(Request $request, Transaction $transaction, PaymentProcessor $processor)
    {
        $this->authorizeDemo($request, $transaction);
        $validated = $request->validate(['outcome' => 'required|in:settlement,cancel']);
        $processor->applyStatus($transaction, [
            'order_id' => $transaction->transaction_number,
            'gross_amount' => $transaction->total_amount,
            'currency' => 'IDR',
            'status_code' => $validated['outcome'] === 'settlement' ? '200' : '202',
            'transaction_status' => $validated['outcome'],
            'transaction_id' => 'demo-'.$transaction->id,
        ]);

        return redirect()->route('subscribe.success', ['transaction' => $transaction->transaction_number]);
    }

    private function authorizeDemo(Request $request, Transaction $transaction): void
    {
        abort_unless(DemoMode::simulatesPayments() && $transaction->user_id === $request->user()->id, 404);
    }
}
