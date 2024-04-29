<?php

namespace App\Http\Controllers;

use App\Http\Requests\PointRequest;
use App\Models\Point;
use Illuminate\Http\Request;

class PointController extends Controller
{
    public function index($user_id){
        $data = Point::where('user_id', $user_id)->get();
        return response()->json([
            'status' => 'success',
            'message' => 'get data success',
            'data' => $data
        ]);
    }

    public function store(PointRequest $request){
        $request->validated();

        $point = new Point([
            'user_id' => $request->user_id,
            'point' => $request->point
        ]);
        $point->save();

        return response()->json([
            'success' => true,
            'message' => 'Point added successfully',
            'data' => $point
        ], 201);

    }

    
}
