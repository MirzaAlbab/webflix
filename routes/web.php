<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SubscribeController;

Route::get('/', function () {
    return view('auth.login');
});

Route::get('/subscribe/plans', [SubscribeController::class, 'showPlans'])
    ->name('subscribe.plans');
Route::get('/subscribe/plan/{plan}', [SubscribeController::class, 'checkoutPlan'])
    ->name('subscribe.plan');