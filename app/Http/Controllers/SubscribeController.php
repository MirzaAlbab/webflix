<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Plan;

class SubscribeController extends Controller
{
    public function showPlans()
    {
        // Logic to retrieve and display subscription plans
        $plans = Plan::all();
        return view('subscribe.plans', compact('plans'));
    }
}
