<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\BannerController;
use App\Http\Controllers\BoxPromoController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CartRewardController;
use App\Http\Controllers\FirebasePushController;
use App\Http\Controllers\HelpCenterController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\OrderRewardController;
use App\Http\Controllers\OtpController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\PointController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\RewardProductController;
use App\Http\Controllers\StatisticController;
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
    Route::put('/update-fcm-token', [FirebasePushController::class, 'setToken']);
    Route::post('send-notification', [FirebasePushController::class, 'sendNotificationToAll'])->middleware('auth:sanctum', 'admin');
    Route::get('notification', [FirebasePushController::class, 'getNotifications'])->middleware('auth:sanctum');
});

Route::prefix('product')->group(function () {
    Route::get('/', [ProductController::class, 'index']);
    Route::get('most-popular', [ProductController::class, 'mostPopularProduct']);
    Route::get('/show/{id}', [ProductController::class, 'show'])->middleware('auth:sanctum');
    Route::post('/favorite/{product_id}', [ProductController::class, 'likeProduct'])->middleware('auth:sanctum');
    Route::get('/favorite', [ProductController::class, 'getFavorite'])->middleware('auth:sanctum');

    Route::group(['middleware' => ['auth:sanctum', 'admin']], function () {
        Route::post('/store', [ProductController::class, 'store']);
        Route::post('/update/{id}', [ProductController::class, 'update']);
        Route::put('update-stock/{id}', [ProductController::class, 'updateStatusStock']);
        Route::delete('/delete/{id}', [ProductController::class, 'destroy']);
    });
});

Route::prefix('voucher')->group(function () {
    Route::get('/', [VoucherController::class, 'index']);
    Route::get('/show/{id}', [VoucherController::class, 'show']);
    Route::get('/active', [VoucherController::class, 'activeVouchers'])->middleware('auth:sanctum');
    Route::post('/redeem', [VoucherController::class, 'redeemVoucher'])->middleware('auth:sanctum');

    Route::group(['middleware' => ['auth:sanctum', 'admin']], function () {
        Route::post('/store', [VoucherController::class, 'store']);
        Route::post('/update/{id}', [VoucherController::class, 'update']);
        Route::delete('/delete/{id}', [VoucherController::class, 'destroy']);
    });
});

Route::prefix('cart')->group(function () {
    Route::get('/', [CartController::class, 'showCartByUser'])->middleware('auth:sanctum');
    Route::get('/item/{cartItemId}', [CartController::class, 'showCartItemById'])->middleware('auth:sanctum');
    Route::post('/store', [CartController::class, 'storeCart'])->middleware('auth:sanctum');
    Route::post('/apply-voucher', [CartController::class, 'applyVoucher'])->middleware('auth:sanctum');
    Route::post('/remove-voucher', [CartController::class, 'removeVoucher'])->middleware('auth:sanctum');
    Route::put('/update/{id}', [CartController::class, 'updateCartItem'])->middleware('auth:sanctum');
    Route::patch('/update-quantity/{id}', [CartController::class, 'updateCartItemQuantity'])->middleware('auth:sanctum');
    Route::delete('/delete/{cartItemID}', [CartController::class, 'deleteCartItem'])->middleware('auth:sanctum');
});

Route::prefix('cart-reward')->group(function () {
    Route::get('/', [CartRewardController::class, 'showCartByUser'])->middleware('auth:sanctum');
    Route::get('/item/{cartItemId}', [CartRewardController::class, 'showCartItemById'])->middleware('auth:sanctum');
    Route::post('/store', [CartRewardController::class, 'storeCart'])->middleware('auth:sanctum');
    Route::put('/update/{id}', [CartRewardController::class, 'updateCartItem'])->middleware('auth:sanctum');
    Route::patch('/update-quantity/{id}', [CartRewardController::class, 'updateCartItemQuantity'])->middleware('auth:sanctum');
    Route::delete('/delete/{cartItemId}', [CartRewardController::class, 'deleteCartItem'])->middleware('auth:sanctum');
});

Route::prefix('point')->group(function () {
    Route::get('/', [PointController::class, 'index'])->middleware('auth:sanctum');
    Route::post('/add', [PointController::class, 'addPoints'])->middleware('auth:sanctum');
});

Route::prefix('reward-product')->group(function () {
    Route::get('/', [RewardProductController::class, 'index']);
    Route::get('/show/{id}', [RewardProductController::class, 'show']);

    Route::group(['middleware' => ['auth:sanctum', 'admin']], function () {
        Route::post('/store', [RewardProductController::class, 'store']);
        Route::post('/update/{id}', [RewardProductController::class, 'update']);
        Route::put('update-stock/{id}', [RewardProductController::class, 'updateStatusStock']);
        Route::delete('/delete/{id}', [RewardProductController::class, 'destroy']);
    });
});

Route::prefix('payment')->group(function () {
    Route::post('/', [PaymentController::class, 'store'])->middleware('auth:sanctum');
    Route::post('/webhook', [PaymentController::class, 'webhook']);
});

Route::prefix('filter')->group(function () {
    Route::get('/product', [ProductController::class, 'filter']);
    Route::get('/reward-product', [RewardProductController::class, 'filter']);
});

Route::prefix('order')->group(function () {
    Route::get('/get-order', [OrderController::class, 'getOrderAdmin']);
    Route::get('/history', [OrderController::class, 'index'])->middleware('auth:sanctum');
    Route::get('/{id}', [OrderController::class, 'show'])->middleware('auth:sanctum');
    Route::post('/store', [OrderController::class, 'storeRegularOrder'])->middleware('auth:sanctum');

    Route::group(['middleware' => ['auth:sanctum', 'admin']], function () {
        Route::get('/get-order', [AdminController::class, 'getOrderAdmin']);
        Route::put('/update-status/{id}', [AdminController::class, 'updateStatusOrder']);
        Route::put('/update-status-siap/{id}', [AdminController::class, 'updateStatusOrderSiap']);
        Route::put('/update-status-selesai/{id}', [AdminController::class, 'updateStatusOrderSelesai']);
    });
});

Route::prefix('order-reward')->group(function () {
    Route::get('/history', [OrderRewardController::class, 'index'])->middleware('auth:sanctum');
    Route::get('/{id}', [OrderRewardController::class, 'show'])->middleware('auth:sanctum');
    Route::post('/store', [OrderRewardController::class, 'store'])->middleware('auth:sanctum');
});

Route::prefix('status-store')->group(function () {
    Route::get('/', [StatusStoreController::class, 'storeStatus'])->middleware(['auth:sanctum']);
    Route::put('/update', [StatusStoreController::class, 'updateStoreStatus'])->middleware(['auth:sanctum', 'admin']);
});

Route::prefix('banner')->group(function () {
    Route::get('/', [BannerController::class, 'index']);
    Route::get('/show/{id}', [BannerController::class, 'show']);

    Route::group(['middleware' => ['auth:sanctum', 'admin']], function () {
        Route::post('/store', [BannerController::class, 'store']);
        Route::post('/update/{id}', [BannerController::class, 'update']);
        Route::delete('/delete/{id}', [BannerController::class, 'destroy']);
    });
});

Route::prefix('box-promo')->group(function () {
    Route::get('/', [BoxPromoController::class, 'index']);
    Route::get('/show/{id}', [BoxPromoController::class, 'show']);

    Route::group(['middleware' => ['auth:sanctum', 'admin']], function () {
        Route::post('/store', [BoxPromoController::class, 'store']);
        Route::post('/update/{id}', [BoxPromoController::class, 'update']);
        Route::delete('/delete/{id}', [BoxPromoController::class, 'destroy']);
    });
});

Route::prefix('help-center')->group(function () {
    Route::get('/', [HelpCenterController::class, 'index']);

    Route::group(['middleware' => ['auth:sanctum', 'admin']], function () {
        Route::post('store', [HelpCenterController::class, 'store']);
        Route::put('update/{id}', [HelpCenterController::class, 'update']);
        Route::delete('delete/{id}', [HelpCenterController::class, 'destroy']);
    });
});

Route::prefix('reorder')->group(function () {
    Route::post('{orderId}', [OrderController::class, 'reorder'])->middleware('auth:sanctum');
    Route::post('reward/{orderId}', [OrderRewardController::class, 'reorderReward'])->middleware('auth:sanctum');
});

Route::prefix('review')->group(function () {
    Route::get('', [ReviewController::class, 'index'])->middleware('auth:sanctum', 'admin');
    Route::post('/{orderId}', [ReviewController::class, 'store'])->middleware('auth:sanctum');
});

Route::get('/show-weekly', [StatisticController::class, 'showWeeklyStatistic']);
