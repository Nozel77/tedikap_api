<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\OtpController;
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
