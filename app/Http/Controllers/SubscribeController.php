<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Plan;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Auth;

class SubscribeController extends Controller implements HasMiddleware
{
    /**
     * The middleware that should be applied to the controller.
     *
     * @return array
     */
    public static function middleware()
    {
        return [
            'auth', // Ensure the user is authenticated
        ];
    }


    public function showPlans()
    {
        // Logic to retrieve and display subscription plans
        $plans = Plan::all();
        return view('subscribe.plans', compact('plans'));
    }

    public function checkoutPlan(Plan $plan)
    {
        $user = Auth::user();
        return view('subscribe.checkout', compact('plan', 'user'));
    }

    public function processCheckout(Request $request)
    {

        $user = Auth::user();
        $plan = Plan::findOrFail($request->plan_id);

        // Logic to create a membership for the user
        $user->memberships()->create([
            'plan_id' => $plan->id,
            'start_date' => now(),
            'end_date' => now()->addDays($plan->duration),
            'active' => true,
        ]);

        return redirect()->route('subscribe.success');
    }
    public function checkoutSuccess()
    {
        return view('subscribe.success');
    }
}
