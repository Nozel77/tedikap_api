<?php

namespace App\Http\Controllers;

use App\Http\Requests\PointRequest;
use App\Http\Resources\PointResource;
use App\Models\Point;

class PointController extends Controller
{
    public function index($user_id)
    {
        // Periksa apakah user_id ada di tabel points
        $userExists = Point::where('user_id', $user_id)->exists();

        // Jika user_id tidak ditemukan, kembalikan respons dengan points 0
        if (! $userExists) {
            return response()->json(['user_id' => $user_id, 'points' => 0]);
        }

        // Jika user_id ditemukan, ambil data points dan kirimkan sebagai response
        $data = Point::where('user_id', $user_id)->get();

        return PointResource::collection($data);
    }

    // public function store(PointRequest $request)
    // {
    //     $request->validated();

    //     $point = new Point([
    //         'user_id' => $request->user_id,
    //         'point' => $request->point,
    //     ]);
    //     $point->save();

    //     return response()->json([
    //         'success' => true,
    //         'message' => 'Point added successfully',
    //         'data' => $point,
    //     ], 201);

    // }

    // public function update(PointRequest $request, $user_id)
    // {
    //     $request->validated();

    //     $point = Point::find($user_id);

    //     $point->update([
    //         'point' => $request->point,
    //     ]);

    //     return $this->resUpdateData($point);
    // }

    // public function storeOrUpdate(PointRequest $request)
    // {
    //     $request->validated();

    //     $existingPoint = Point::where('user_id', $request->user_id)->first();

    //     if ($existingPoint) {
    //         $existingPoint->update([
    //             'point' => $request->point,
    //         ]);

    //         return response()->json([
    //             'success' => true,
    //             'message' => 'Point updated successfully',
    //             'data' => $existingPoint,
    //         ], 200);
    //     } else {
    //         $point = new Point([
    //             'user_id' => $request->user_id,
    //             'point' => $request->point,
    //         ]);
    //         $point->save();

    //         return response()->json([
    //             'success' => true,
    //             'message' => 'Point added successfully',
    //             'data' => $point,
    //         ], 201);
    //     }
    // }
}
