<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\OtpController;
use App\Http\Controllers\PointController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\PromoController;
use App\Models\Point;
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

Route::group(['prefix' => 'auth'], function () {
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login']);
    Route::post('/otp', [OtpController::class, 'sendOtp']);
    Route::post('/resetpw', [AuthController::class, 'resetPassword']);
});

Route::group(['prefix' => 'product'], function () {
    Route::get('/', [ProductController::class, 'index']);
    Route::post('/store', [ProductController::class, 'store']);
    Route::get('/show/{id}', [ProductController::class, 'show']);
    Route::post('/update/{id}', [ProductController::class, 'update']);
    Route::delete('/delete/{id}', [ProductController::class, 'destroy']);
});

Route::group(['prefix' => 'promo'], function () {
    Route::get('/', [PromoController::class, 'index']);
    Route::post('/store', [PromoController::class, 'store']);
    Route::get('/show/{id}', [PromoController::class, 'show']);
    Route::post('/update/{id}', [PromoController::class, 'update']);
    Route::delete('/delete/{id}', [PromoController::class, 'destroy']);
});

Route::group(['prefix' => 'order'], function () {
    Route::get('/', [OrderController::class, 'index']);
    Route::post('/store', [OrderController::class, 'store']);
    Route::get('/show/{id}', [OrderController::class, 'show']);
    Route::put('/update/{id}', [OrderController::class, 'update']);
    Route::delete('/delete/{id}', [OrderController::class, 'destroy']);
});

Route::group(['prefix' => 'point'], function () {
    Route::get('/{user_id}', [PointController::class, 'index']);
    Route::post('/store', [PointController::class, 'store']);
    
});

