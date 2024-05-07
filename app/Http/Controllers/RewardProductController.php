<?php

namespace App\Http\Controllers;

use App\Http\Requests\RewardProductRequest;
use App\Models\RewardProduct;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class RewardProductController extends Controller
{
    public function index(){
        $data = RewardProduct::all();
        return response()->json([
            'status' => 'success',
            'data' => $data
        ]);
    }

    public function store(RewardProductRequest $request){
        $request->validated();

        $imageName = time().'.'.$request->image->extension();  
        $request->image->move(public_path('images'), $imageName);

        $rewardProduct = new RewardProduct([
            'name' => $request->name,
            'point_price' => $request->point_price,
            'category' => $request->category,
            'image' => $imageName
        ]);
        $rewardProduct->save();

        return response()->json([
            'success' => true,
            'message' => 'reward product created successfully',
            'data' => $rewardProduct
        ], 201);
    }

    public function show($id){
        $data = RewardProduct::find($id);
        if (!$data) {
            return response()->json([
                'status' => 'error',
                'message' => 'Reward product not found'
            ], 404);
        }
        return response()->json([
            'status' => 'success',
            'message' => 'get data successfully',
            'data' => $data
        ]);
    }

    public function update(RewardProductRequest $request, $id)
    {
    $request->validated();

    $rewardProduct = RewardProduct::find($id);

    if (!$rewardProduct) {
        return response()->json(['error' => 'reward product not found'], 404);
    }

    if ($request->hasFile('image')) {
        Storage::delete('public/images/'.$rewardProduct->image);

        $imageName = time().'.'.$request->image->extension();
        $request->image->move(public_path('images'), $imageName);

        $rewardProduct->update([
            'name' => $request->name,
            'point_price' => $request->point_price,
            'category' => $request->category,
            'image' => $imageName
        ]);
    } else {
        $rewardProduct->update([
            'name' => $request->name,
            'point_price' => $request->point_price,
            'category' => $request->category,
            'image' => $request->image
        ]);
    }

    return response()->json([
        'success' => true,
        'message' => 'Product updated successfully',
        'data' => $rewardProduct
    ], 200);
}

public function destroy($id)
{
    $rewardProduct = RewardProduct::find($id);

    if (!$rewardProduct) {
        return response()->json(['error' => 'reward product not found'], 404);
    }

    Storage::delete('public/images/'. $rewardProduct->image);
    $rewardProduct->delete();

    return response()->json([
        'success' => true,
        'message' => 'reward product deleted successfully'
    ], 200);
}

}
