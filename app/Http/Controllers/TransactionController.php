<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Midtrans\Config;
use Midtrans\Snap;

class TransactionController extends Controller
{
    public function __construct()
    {
        Config::$serverKey = config('midtrans.server_key');
        Config::$isProduction = config('midtrans.is_production');
        Config::$isSanitized = config('midtrans.is_sanitized');
        Config::$is3ds = config('midtrans.is_3ds');

    }

    public function checkout(Request $request)
    {
        $user = Auth::user();
        // Validate request data
        $request->validate([
            'plan_id' => 'required|exists:plans,id',
        ]);

        $transactionNumber = 'ORDER-' . time() . '-' . $user->id;

        // Create transaction
        $transaction = Transaction::create([
            'user_id' => $user->id,
            'plan_id' => $request->plan_id,
            'transaction_number' => $transactionNumber,
            'total_amount' => $request->amount, // Example amount, replace with actual plan price
            'payment_status' => 'pending',
        ]);

        // Prepare Midtrans transaction data
        $payload = [
            'transaction_details' => [
                'order_id' => $transaction->transaction_number,
                'gross_amount' =>(int) $transaction->total_amount,
            ],
            'customer_details' => [
                'first_name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone ?? '0000000000000',
            ],
            'item_details' => [
                [
                    'id' => $transaction->plan_id,
                    'price' => (int) $transaction->total_amount,
                    'quantity' => 1,
                    'name' => $transaction->plan->title, // Replace with actual plan name
                ],
            ],
        ];

        // Create Midtrans Snap token
        try{
            // Get Snap Payment Page URL
            $snapToken = Snap::getSnapToken($payload);
            $transaction->update(['snap_token' => $snapToken]);
            return response()->json([
                'status' => 'success',
                'snap_token' => $snapToken,
                'message' => 'Transaction created successfully',
            ]);

        }catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create transaction: ' . $e->getMessage(),
            ], 500);
        }

    }
}
