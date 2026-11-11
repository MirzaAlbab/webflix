<?php

use App\Http\Controllers\CategoryController;
use App\Http\Controllers\MovieController;
use App\Http\Controllers\SubscribeController;
use App\Http\Controllers\TransactionController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/', [MovieController::class, 'all'])->name('catalog');

Route::get('/home', [MovieController::class, 'index'])->name('home');
Route::get('/movies', [MovieController::class, 'all'])->name('movies.index');
Route::get('/movies/search', [MovieController::class, 'search'])->name('movies.search');
Route::get('/movies/{movie:slug}', [MovieController::class, 'show'])->name('movies.show');
Route::get('/categories/{category:slug}', [CategoryController::class, 'show'])->name('categories.show');

Route::post('/checkout', [TransactionController::class, 'checkout'])->middleware('auth')->name('checkout');
Route::get('/transactions/{transaction:transaction_number}/status', [TransactionController::class, 'status'])->middleware('auth')->name('transactions.status');

Route::post('logout', function (Request $request) {
    return app(Laravel\Fortify\Http\Controllers\AuthenticatedSessionController::class)->destroy($request);
})->middleware(['auth', 'logout.device'])->name('logout');

Route::get('/subscribe/plans', [SubscribeController::class, 'showPlans'])->name('subscribe.plans');
Route::get('/subscribe/plan/{plan}', [SubscribeController::class, 'checkoutPlan'])->name('subscribe.checkout');
Route::post('subscribe/checkout', [TransactionController::class, 'checkout'])->middleware('auth')->name('subscribe.process');
Route::get('/subscribe/success', [SubscribeController::class, 'checkoutSuccess'])->name('subscribe.success');

Route::middleware('auth')->group(function () {
    Route::get('/demo/payments/{transaction:transaction_number}', [\App\Http\Controllers\DemoPaymentController::class, 'show'])->name('demo.payment');
    Route::post('/demo/payments/{transaction:transaction_number}', [\App\Http\Controllers\DemoPaymentController::class, 'complete'])->name('demo.payment.complete');
});

Route::get('/demo/player', function () {
    abort_unless(\App\Support\DemoMode::enabled(), 404);

    return view('subscribe.demo-player');
})->middleware(['auth', 'subscribed'])->name('demo.player');
