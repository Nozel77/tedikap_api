<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Http\Requests\UserRequest;
use App\Http\Resources\UserResource;
use App\Models\Otp;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class UserController extends Controller
{
    public function register(RegisterRequest $request)
    {
        $data = $request->validated();

        $otpRecord = Otp::where('email', $data['email'])->where('otp', $data['otp'])->first();

        if (! $otpRecord) {
            return response()->json([
                'message' => 'Invalid OTP',
            ], 400);
        }

        if ($otpRecord->isExpired()) {
            $otpRecord->delete();

            return response()->json([
                'message' => 'OTP expired. Please request a new one.',
            ], 400);
        }

        $otpRecord->delete();

        $fcmToken = $request->input('fcm_token');

        $user = new User($data);
        $user->password = Hash::make($data['password']);
        $user->email_verified_at = now();
        $whatsappMessage = urlencode('Halo Tedikap, Saya membutuhkan bantuan');
        $user->whatsapp_service = "https://wa.me/62895395343223?text={$whatsappMessage}";
        $user->save();

        if ($fcmToken) {
            $user->update([
                'fcm_token' => $fcmToken,
            ]);
        }

        return response()->json([
            'message' => 'User created successfully',
            'data' => new UserResource($user),
            'token' => $user->createToken('tedikap')->plainTextToken,
        ], 201);
    }

    public function me()
    {
        $user = Auth::user();

        return new UserResource($user);
    }

    public function login(LoginRequest $request)
    {
        $data = $request->validated();

        $fcmToken = $request->input('fcm_token');

        if (! Auth::attempt($data)) {
            return response()->json([
                'message' => 'Invalid credentials',
            ], 401);
        }

        $user = User::where('email', $data['email'])->first();

        if ($fcmToken) {
            $user->update([
                'fcm_token' => $fcmToken,
            ]);
        }

        $token = $user->createToken('tedikap')->plainTextToken;

        return response()->json([
            'message' => 'Login successful',
            'data' => new UserResource($user),
            'token' => $token,
        ], 200);
    }

    public function resetPassword(Request $request)
    {
        $validatedData = $request->validate([
            'token' => 'required|string',
            'email' => 'required|email',
            'password' => 'required|string|min:6|confirmed',
        ]);

        $email = Cache::get("password-reset-{$validatedData['token']}");

        if (! $email || $email !== $validatedData['email']) {
            return response()->json([
                'message' => 'Invalid or expired token',
            ], 400);
        }

        $user = User::where('email', $validatedData['email'])->first();

        if (! $user) {
            return response()->json([
                'message' => 'User not found',
            ], 404);
        }

        $user->update([
            'password' => Hash::make($validatedData['password']),
        ]);

        Cache::forget("password-reset-{$validatedData['token']}");

        Otp::where('email', $validatedData['email'])->delete();

        return response()->json([
            'message' => 'Password has been reset successfully',
        ], 200);
    }

    public function updateUser(UserRequest $request)
    {
        $data = $request->validated();

        $user = User::findOrFail(Auth::id());

        $user->fill($data);

        if ($request->hasFile('avatar')) {
            if ($user->avatar) {
                Storage::delete('public/avatar/'.$user->avatar);
            }

            $imageName = time().'.'.$request->file('avatar')->extension();
            $request->file('avatar')->storeAs('avatar', $imageName, 'public');
            $user->avatar = $imageName;
        }

        $user->save();

        return response()->json([
            'message' => 'User updated successfully',
            'data' => new UserResource($user),
        ], 200);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response(['message' => 'Logged Out'], 200);
    }
}
