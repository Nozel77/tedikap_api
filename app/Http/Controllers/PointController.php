<?php

namespace App\Http\Controllers;

use App\Http\Resources\PointResource;
use App\Models\Point;
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
}
