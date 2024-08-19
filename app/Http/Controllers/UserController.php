<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Http\Requests\ResetPassword as RequestsResetPassword;
use App\Http\Requests\UserRequest;
use App\Http\Resources\UserResource;
use App\Models\Otp;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class UserController extends Controller
{
    public function register(RegisterRequest $request)
    {
        $data = $request->validated();

        $fcmToken = $request->input('fcm_token');

        $user = new User($data);
        $user->password = Hash::make($data['password']);
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

    public function resetPassword(RequestsResetPassword $request)
    {
        $request->validate([
            'email' => 'required|email|max:255',
            'otp' => 'required|numeric',
            'password' => 'required|min:6',
        ]);

        $otpData = Otp::where('email', $request->email)->first();

        if (! $otpData) {
            return response([
                'message' => 'OTP not found',
            ], 404);
        }

        if ($otpData->otp != $request->otp) {
            return response([
                'message' => 'Invalid OTP',
            ], 400);
        }

        $user = User::where('email', $request->email)->first();

        $user->password = bcrypt($request->password);
        $user->save();
        $otpData->delete();

        return response([
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
