<?php

namespace App\Http\Controllers;

use App\Http\Requests\RewardProductRequest;
use App\Http\Requests\RewardProductUpdateRequest;
use App\Http\Resources\RewardProductResource;
use App\Models\RewardProduct;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class RewardProductController extends Controller
{
    public function index()
    {
        $data = RewardProduct::all();

        return RewardProductResource::collection($data);
    }

    public function filter(Request $request)
    {
        $category = $request->input('category');
        $search = $request->input('search');

        $query = RewardProduct::query();

        if ($category) {
            $query->where('category', $category);
        }
        if ($search) {
            $query->where('name', 'like', '%'.$search.'%');
        }

        $result = $query->get();

        if ($result->count() > 0) {
            return RewardProductResource::collection($result);
        } else {
            return $this->resDataNotFound('Product');
        }
    }

    public function store(RewardProductRequest $request)
    {
        $request->validated();

        $imageName = time().'.'.$request->file('image')->extension();
        $request->file('image')->storeAs('reward-product', $imageName, 'public');

        $data = new RewardProduct([
            'name' => $request->name,
            'description' => $request->description,
            'regular_point' => $request->regular_point,
            'large_point' => $request->large_point,
            'category' => $request->category,
            'image' => $imageName,
            'stock' => true,
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

    public function update(RewardProductUpdateRequest $request, $id)
    {
        $rewardProduct = RewardProduct::find($id);

        if (! $rewardProduct) {
            return $this->resDataNotFound('Reward Product');
        }

        $data = $request->validated();

        $rewardProduct->fill($data);

        if ($request->hasFile('image')) {
            Storage::delete('public/reward-product/'.$rewardProduct->image);

            $imageName = time().'.'.$request->image->extension();
            $request->file('image')->storeAs('reward-product', $imageName, 'public');

            $rewardProduct->image = $imageName;
        }

        $rewardProduct->save();

        return $this->resUpdatedData($rewardProduct);
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

    public function updateStatusStock(Request $request, $id)
    {
        $product = RewardProduct::find($id);

        if (! $product) {
            return $this->resDataNotFound('Reward Product');
        }

        $product->stock = $request->stock;
        $product->save();

        return $this->resUpdatedData($product);
    }
}
