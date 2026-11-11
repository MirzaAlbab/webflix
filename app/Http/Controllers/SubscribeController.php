<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;

class SubscribeController extends Controller implements HasMiddleware
{
    public static function middleware()
    {
        return ['auth'];
    }

    public function showPlans()
    {
        return view('subscribe.plans', ['plans' => Plan::all()]);
    }

    public function checkoutPlan(Plan $plan)
    {
        return view('subscribe.checkout', ['plan' => $plan]);
    }

    public function checkoutSuccess(Request $request)
    {
        if (! $request->filled('transaction')) {
            return redirect()->route('subscribe.plans');
        }

        $transaction = Transaction::where('user_id', $request->user()->id)
            ->where('transaction_number', $request->query('transaction'))
            ->firstOrFail();

        return view('subscribe.success', compact('transaction'));
    }
}
