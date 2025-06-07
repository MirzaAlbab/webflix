<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Plan;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

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
}
