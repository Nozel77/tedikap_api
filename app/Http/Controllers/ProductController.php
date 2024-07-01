<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProductRequest;
use App\Http\Requests\ProductUpdateRequest;
use App\Http\Resources\FavoriteResource;
use App\Http\Resources\ProductResource;
use App\Models\Favorite;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class ProductController extends Controller
{
    public function index()
    {
        return ProductResource::collection(Product::all());
    }

    public function store(ProductRequest $request)
    {
        $request->validated();

        $imageName = time().'.'.$request->file('image')->extension();
        $request->file('image')->storeAs('product', $imageName, 'public');

        $data = new Product([
            'name' => $request->name,
            'description' => $request->description,
            'regular_price' => $request->regular_price,
            'large_price' => $request->large_price,
            'category' => $request->category,
            'image' => $imageName,
        ]);
        $data->save();

        return new ProductResource($data);
    }

    public function show($id)
    {
        $data = Product::find($id);
        if (! $data) {
            return $this->resDataNotFound('Product');
        }

        return new ProductResource($data);
    }

    public function update(ProductUpdateRequest $request, $id)
    {
        $request->validated();

        $data = Product::find($id);

        if (! $data) {
            return $this->resDataNotFound('Product');
        }

        if ($request->hasFile('image')) {
            Storage::delete('public/product/'.$data->image);

            $imageName = time().'.'.$request->image->extension();
            $request->file('image')->storeAs('product', $imageName, 'public');

            $data->update([
                'name' => $request->name,
                'description' => $request->description,
                'regular_price' => $request->regular_price,
                'large_price' => $request->large_price,
                'category' => $request->category,
                'image' => $imageName,
            ]);
        } else {
            $data->update([
                'name' => $request->name,
                'description' => $request->description,
                'regular_price' => $request->regular_price,
                'large_price' => $request->large_price,
                'category' => $request->category,
            ]);
        }

        return $this->resUpdatedData($data);
    }

    public function destroy($id)
    {
        $data = Product::find($id);

        if (! $data) {
            return $this->resDataNotFound('Product');
        }

        Storage::delete('public/images/'.$data->image);
        $data->delete();

        return $this->resDataDeleted();
    }

    public function filter(Request $request)
    {
        $category = $request->input('category');
        $search = $request->input('search');

        $query = Product::query();

        if ($category) {
            $query->where('category', $category);
        }
        if ($search) {
            $query->where('name', 'like', '%'.$search.'%');
        }

        $result = $query->get();

        if ($result->count() > 0) {
            return ProductResource::collection($result);
        } else {
            return $this->resDataNotFound('Product');
        }
    }

    public function likeProduct(Request $request, $product_id)
    {
        $user_id = Auth::id();

        if (! $user_id) {
            return response()->json(['error' => 'User not authenticated'], 401);
        }

        $product = Product::whereId($product_id)->first();

        if (! $product) {
            return response()->json(['error' => 'Product not found'], 404);
        }

        $unlike_post = Favorite::where('user_id', $user_id)->where('product_id', $product_id)->delete();
        if ($unlike_post) {
            return response()->json(['message' => 'Unliked'], 200);
        }

        $like_post = Favorite::create([
            'user_id' => $user_id,
            'product_id' => $product_id,
        ]);

        if ($like_post) {
            return response()->json(['message' => 'Liked'], 200);
        }

        return response()->json(['error' => 'Unable to like product'], 500);
    }

    public function getFavorite()
    {
        $user_id = Auth::id();

        $data = Favorite::where('user_id', $user_id)->get();

        if ($data->count() > 0) {
            return FavoriteResource::collection($data);
        } else {
            return response()->json(['user_id' => $user_id, 'favorite' => []]);
        }
    }
}
