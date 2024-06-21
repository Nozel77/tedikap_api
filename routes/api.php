<?php

use App\Http\Controllers\CartController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\OtpController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\PointController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\RewardItemController;
use App\Http\Controllers\RewardProductController;
use App\Http\Controllers\StatusStoreController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\VoucherController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::prefix('user')->group(function () {
    Route::post('register', [UserController::class, 'register']);
    Route::post('login', [UserController::class, 'login']);
    Route::post('otp', [OtpController::class, 'sendOtp']);
    Route::post('reset-pw', [UserController::class, 'resetPassword']);
    Route::get('get-user', [UserController::class, 'me'])->middleware('auth:sanctum');
    Route::post('update-profile', [UserController::class, 'updateUser'])->middleware('auth:sanctum');
    Route::post('logout', [UserController::class, 'logout'])->middleware('auth:sanctum');
});

Route::prefix('product')->group(function () {
    Route::get('/', [ProductController::class, 'index']);
    Route::post('/store', [ProductController::class, 'store']);
    Route::get('/show/{id}', [ProductController::class, 'show']);
    Route::post('/update/{id}', [ProductController::class, 'update']);
    Route::delete('/delete/{id}', [ProductController::class, 'destroy']);
    Route::post('/favorite/{product_id}', [ProductController::class, 'likeProduct'])->middleware('auth:sanctum');
    Route::get('/favorite', [ProductController::class, 'getFavorite'])->middleware('auth:sanctum');
});

Route::prefix('voucher')->group(function () {
    Route::get('/', [VoucherController::class, 'index']);
    Route::get('/active', [VoucherController::class, 'activeVouchers'])->middleware('auth:sanctum');
    Route::post('/redeem', [VoucherController::class, 'redeemVoucher'])->middleware('auth:sanctum');
    Route::post('/store', [VoucherController::class, 'store']);
    Route::get('/show/{id}', [VoucherController::class, 'show']);
    Route::post('/update/{id}', [VoucherController::class, 'update']);
    Route::delete('/delete/{id}', [VoucherController::class, 'destroy']);
});

Route::prefix('cart')->group(function () {
    Route::get('/', [CartController::class, 'showCartByUser'])->middleware('auth:sanctum');
    Route::post('/store', [CartController::class, 'storeCart'])->middleware('auth:sanctum');
    Route::post('/apply-voucher', [CartController::class, 'applyVoucher'])->middleware('auth:sanctum');
    Route::post('/remove-voucher', [CartController::class, 'removeVoucher'])->middleware('auth:sanctum');
    Route::put('/update/{id}', [CartController::class, 'updateCartItem'])->middleware('auth:sanctum');
    Route::patch('/update-quantity/{id}', [CartController::class, 'updateCartItemQuantity'])->middleware('auth:sanctum');
    Route::delete('/delete', [CartController::class, 'deleteCartItem'])->middleware('auth:sanctum');
});

Route::prefix('point')->group(function () {
    Route::get('/{user_id}', [PointController::class, 'index']);
});

Route::prefix('reward-product')->group(function () {
    Route::get('/', [RewardProductController::class, 'index']);
    Route::post('/store', [RewardProductController::class, 'store']);
    Route::get('/show/{id}', [RewardProductController::class, 'show']);
    Route::post('/update/{id}', [RewardProductController::class, 'update']);
    Route::delete('/delete/{id}', [RewardProductController::class, 'destroy']);
});

Route::prefix('reward-item')->group(function () {
    Route::get('/', [RewardItemController::class, 'index']);
    Route::post('/store', [RewardItemController::class, 'store']);
    Route::delete('/delete/{id}', [RewardItemController::class, 'destroy']);
});

Route::prefix('payment')->group(function () {
    Route::post('/', [PaymentController::class, 'store']);
    Route::post('/notification', [PaymentController::class, 'notification']);
    Route::post('/callback', [PaymentController::class, 'paymentCallback']);
});

Route::prefix('filter')->group(function () {
    Route::get('/product', [ProductController::class, 'filter']);
});

Route::prefix('order')->group(function () {
    Route::get('/', [OrderController::class, 'index'])->middleware('auth:sanctum');
    Route::post('/store', [OrderController::class, 'store'])->middleware('auth:sanctum');
});

Route::prefix('status-store')->group(function () {
    Route::get('/', [StatusStoreController::class, 'storeStatus']);
    Route::put('/update', [StatusStoreController::class, 'updateStoreStatus']);
});
