<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Http\Requests\ResetPassword as RequestsResetPassword;
use App\Http\Requests\UserRequest;
use App\Http\Resources\UserResource;
use App\Models\Otp;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function register(RegisterRequest $request)
    {
        $request->validated();

        $userData = [
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ];

        $user = User::create($userData);
        $user = new UserResource($user);
        $token = $user->createToken('tedikap')->plainTextToken;

        return response()->json([
            'user' => $user,
            'token' => $token,
        ], 201);
    }

    public function me()
    {
        $user = Auth::user();

        return response()->json($user);
    }

    public function login(LoginRequest $request)
    {
        $request->validated();

        $user = User::whereEmail($request->email)->first();
        if (! $user || ! Hash::check($request->password, $user->password)) {
            return response()->json([
                'message' => 'Invalid credentials',
            ], 401);
        }

        $user = new UserResource($user);
        $token = $user->createToken('tedikap')->plainTextToken;

        return response()->json([
            'user' => $user,
            'token' => $token,
        ], 201);
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

        $user = User::all()->where('id', Auth::id())->first();

        $user->fill($data);

        if ($request->hasFile('avatar')) {
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

    public function logout()
    {
        Auth::guard('web')->logout();

        return response()->json(['message' => 'Successfully logged out']);
    }
}
