<?php

namespace App\Http\Controllers;

use App\Http\Requests\RewardProductRequest;
use App\Http\Resources\RewardProductResource;
use App\Models\RewardProduct;
use Illuminate\Support\Facades\Storage;

class RewardProductController extends Controller
{
    public function index()
    {
        $data = RewardProduct::all();

        return RewardProductResource::collection($data);
    }

    public function store(RewardProductRequest $request)
    {
        $request->validated();

        $imageName = time().'.'.$request->file('image')->extension();
        $request->file('image')->storeAs('reward-product', $imageName, 'public');

        $data = new RewardProduct([
            'name' => $request->name,
            'point_price' => $request->point_price,
            'category' => $request->category,
            'image' => $imageName,
        ]);
        $data->save();

        return $this->resAddData($data);
    }

    public function show($id)
    {
        $data = RewardProduct::find($id);
        if (! $data) {
            return response()->json([
                'status' => 'error',
                'message' => 'Reward product not found',
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'get data successfully',
            'data' => $data,
        ]);
    }

    public function update(RewardProductRequest $request, $id)
    {
        $request->validated();

        $data = RewardProduct::find($id);

        if (! $data) {
            return response()->json(['error' => 'reward product not found'], 404);
        }

        if ($request->hasFile('image')) {
            Storage::delete('public/reward-product/'.$data->image);

            $imageName = time().'.'.$request->image->extension();
            $request->file('image')->storeAs('reward-product', $imageName, 'public');

            $data->update([
                'name' => $request->name,
                'point_price' => $request->point_price,
                'category' => $request->category,
                'image' => $imageName,
            ]);
        } else {
            $data->update([
                'name' => $request->name,
                'point_price' => $request->point_price,
                'category' => $request->category,
                'image' => $request->image,
            ]);
        }

        return $this->resUpdatedData($data);
    }

    public function destroy($id)
    {
        $data = RewardProduct::find($id);

        if (! $data) {
            return response()->json(['error' => 'reward product not found'], 404);
        }

        Storage::delete('public/reward-product/'.$data->image);
        $data->delete();

        return $this->resDataDeleted();
    }
}
