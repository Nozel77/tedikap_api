<?php

namespace App\Http\Controllers;

use App\Http\Resources\PointResource;
use App\Models\Point;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PointController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        $userExists = Point::where('user_id', $user->id)->exists();

        if (! $userExists) {
            return response()->json(['user_id' => $user->id, 'points' => 0]);
        }

        $data = Point::where('user_id', $user->id)->get();

        return PointResource::collection($data);
    }

    public function addPoints(Request $request)
    {
        $user = Auth::user();
        $pointsToAdd = $request->input('point');

        $request->validate([
            'point' => 'required|integer',
        ]);
        $point = Point::firstOrNew(['user_id' => $user->id]);
        $point->point += $pointsToAdd;

        $point->save();

        return response()->json([
            'message' => 'Points added successfully',
            'user_id' => $user->id,
            'points' => $point->point,
        ]);
    }
}
