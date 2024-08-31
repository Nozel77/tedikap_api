<?php

namespace App\Http\Controllers;

use App\Mail\ResetPassword;
use App\Mail\VerifyEmail;
use App\Models\Otp;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class OtpController extends Controller
{
    public function sendOtp(Request $request)
    {
        $request->validate([
            'email' => 'required|email|max:255',
        ]);

        Otp::where('email', $request->email)->delete();

        $user = User::where('email', $request->email)->first();

        if (! $user) {
            return response([
                'message' => 'User not found',
            ], 404);
        }

        $otp = rand(1000, 9999);

        Mail::to($request->email)->send(new ResetPassword($otp, $user->name));

        Otp::create([
            'id' => 'otp-'.Str::uuid(),
            'email' => $request->email,
            'otp' => $otp,
            'expires_at' => Carbon::now()->addMinutes(5),
        ]);

        return response([
            'message' => 'Otp has been sent to your email',
        ], 200);
    }

    public function sendOtpRegister(Request $request)
    {
        $request->validate([
            'email' => 'required|email|max:255',
        ]);

        Otp::where('email', $request->email)->delete();

        $otp = rand(1000, 9999);

        Mail::to($request->email)->send(new VerifyEmail($otp));

        Otp::create([
            'id' => 'otp-'.Str::uuid(),
            'email' => $request->email,
            'otp' => $otp,
        ]);

        return response([
            'message' => 'Otp has been sent to your email',
        ], 200);
    }

    public function verifyOtp(Request $request)
    {
        $request->validate([
            'email' => 'required|email|max:255',
            'otp' => 'required|numeric',
        ]);

        $otpData = Otp::where('email', $request->email)
            ->where('otp', $request->otp)
            ->first();

        if (! $otpData) {
            return response([
                'message' => 'OTP Invalid',
            ], 404);
        }

        if ($otpData->isExpired()) {
            return response([
                'message' => 'OTP has expired',
            ], 400);
        }

        // Generate a new reset token and store it in cache with email
        $resetToken = Str::random(60);

        Cache::put("password-reset-{$resetToken}", $request->email, now()->addMinutes(5));

        return response([
            'message' => 'OTP is valid',
            'reset_token' => $resetToken,
        ], 200);
    }
}
