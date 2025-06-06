<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SubscribeController;
use Illuminate\Http\Request;

Route::get('/', function () {
    return view('auth.login');
});

Route::get('/home', function () {
    return view('home');
})->middleware(['auth', 'check.device.limit'])
    ->name('home');

Route::post('logout', function (Request $request) {
    return app(Laravel\Fortify\Http\Controllers\AuthenticatedSessionController::class)
        ->destroy($request);
})->middleware(['auth', 'logout.device'])->name('logout');

Route::get('/subscribe/plans', [SubscribeController::class, 'showPlans'])
    ->name('subscribe.plans');
Route::get('/subscribe/plan/{plan}', [SubscribeController::class, 'checkoutPlan'])
    ->name('subscribe.checkout');
Route::post('subscribe/checkout', [SubscribeController::class, 'processCheckout'])
    ->name('subscribe.process');
Route::get('/subscribe/success', [SubscribeController::class, 'checkoutSuccess'])
    ->name('subscribe.success');