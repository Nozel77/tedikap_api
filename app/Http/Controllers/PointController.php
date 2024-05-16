<?php

namespace App\Http\Controllers;

use App\Http\Requests\PointRequest;
use App\Models\Point;

class PointController extends Controller
{
    public function index($user_id)
    {
        $data = Point::where('user_id', $user_id)->get();

        return response()->json([
            'status' => 'success',
            'message' => 'get data success',
            'data' => $data,
        ]);
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

        return response()->json([
            'success' => true,
            'message' => 'Point update successfully',
            'data' => $point,
        ], 201);
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
