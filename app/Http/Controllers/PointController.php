<?php

namespace App\Http\Controllers;

use App\Http\Requests\PointRequest;
use App\Models\Point;

class PointController extends Controller
{
    public function index($user_id)
    {
        $userExists = Point::where('user_id', $user_id)->exists();

        if (! $userExists) {
            return response()->json(['error' => 'user_id not found'], 404);
        }

        $data = Point::where('user_id', $user_id)->get();

        if ($data->isEmpty()) {
            return response()->json(['user_id' => $user_id, 'points' => 0]);
        }

        return $this->resShowData($data);
    }

    public function store(PointRequest $request)
    {
        $request->validated();

        $point = new Point([
            'user_id' => $request->user_id,
            'point' => $request->point,
        ]);
        $point->save();

        return response()->json([
            'success' => true,
            'message' => 'Point added successfully',
            'data' => $point,
        ], 201);

    }

    public function update(PointRequest $request, $user_id)
    {
        $request->validated();

        $point = Point::find($user_id);

        $point->update([
            'point' => $request->point,
        ]);

        return $this->resUpdateData($point);
    }

    public function storeOrUpdate(PointRequest $request)
    {
        $request->validated();

        $existingPoint = Point::where('user_id', $request->user_id)->first();

        if ($existingPoint) {
            $existingPoint->update([
                'point' => $request->point,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Point updated successfully',
                'data' => $existingPoint,
            ], 200);
        } else {
            $point = new Point([
                'user_id' => $request->user_id,
                'point' => $request->point,
            ]);
            $point->save();

            return response()->json([
                'success' => true,
                'message' => 'Point added successfully',
                'data' => $point,
            ], 201);
        }
    }
}
