<?php

namespace App\Http\Controllers;

use App\Http\Resources\PointResource;
use App\Models\Point;

class PointController extends Controller
{
    public function index($user_id)
    {
        $userExists = Point::where('user_id', $user_id)->exists();

        if (! $userExists) {
            return response()->json(['user_id' => $user_id, 'points' => 0]);
        }

        $data = Point::where('user_id', $user_id)->get();

        return PointResource::collection($data);
    }
}
