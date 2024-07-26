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

        Storage::delete('public/product/'.$data->image);
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

        $product = Product::find($product_id);

        if (! $product) {
            return response()->json(['error' => 'Product not found'], 404);
        }

        $favorite = Favorite::where('user_id', $user_id)->where('product_id', $product_id)->first();

        if ($favorite) {
            $favorite->delete();
            $isLiked = false;
            $message = 'Unliked';
        } else {
            Favorite::create([
                'user_id' => $user_id,
                'product_id' => $product_id,
            ]);
            $isLiked = true;
            $message = 'Liked';
        }

        $productResource = new ProductResource($product);

        return response()->json([
            'message' => $message,
            'status' => $isLiked ? 'liked' : 'unliked',
            'product' => $productResource,
        ], 200);
    }

    public function getFavorite()
    {
        $user_id = Auth::id();

        $favorites = Favorite::where('user_id', $user_id)->with('product')->get();

        if ($favorites->count() > 0) {
            return FavoriteResource::collection($favorites);
        } else {
            return response()->json(['user_id' => $user_id, 'favorite' => []]);
        }
    }

    public function mostPopularProduct()
    {
        $product = Product::withCount('favorites')->orderBy('favorites_count', 'desc')->take(10)->get();

        return ProductResource::collection($product);
    }
}
