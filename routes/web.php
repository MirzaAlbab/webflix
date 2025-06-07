<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SubscribeController;

Route::get('/', function () {
    return view('auth.login');
});

Route::get('/home', function () {
    return view('home');
})->middleware(['auth'])
    ->name('home');

Route::get('/subscribe/plans', [SubscribeController::class, 'showPlans'])
    ->name('subscribe.plans');
Route::get('/subscribe/plan/{plan}', [SubscribeController::class, 'checkoutPlan'])
    ->name('subscribe.checkout');
Route::post('subscribe/checkout', [SubscribeController::class, 'processCheckout'])
    ->name('subscribe.process');
Route::get('/subscribe/success', [SubscribeController::class, 'checkoutSuccess'])
    ->name('subscribe.success');