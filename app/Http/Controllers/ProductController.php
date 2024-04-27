<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProductRequest;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProductController extends Controller
{
    public function index(){
        $data = Product::all();
        return response()->json([
            'status' => 'success',
            'message' => 'get data success',
            'data' => $data
        ]);
    }

    public function store(ProductRequest $request){
        $request->validated();

        $imageName = time().'.'.$request->image->extension();  
        $request->image->move(public_path('images'), $imageName);

        $product = new Product([
            'name' => $request->name,
            'description' => $request->description,
            'price' => $request->price,
            'category' => $request->category,
            'image' => $imageName
        ]);
        $product->save();

        return response()->json([
            'success' => true,
            'message' => 'Product created successfully',
            'data' => $product
        ], 201);
    }

    public function show($id){
        $data = Product::find($id);
        if (!$data) {
            return response()->json([
                'status' => 'error',
                'message' => 'Product not found'
            ], 404);
        }
        return response()->json([
            'status' => 'success',
            'message' => 'get data successfully',
            'data' => $data
        ]);
    }

    public function update(ProductRequest $request, $id)
    {
    $request->validated();

    $product = Product::find($id);

    if (!$product) {
        return response()->json(['error' => 'Product not found'], 404);
    }

    if ($request->hasFile('image')) {
        Storage::delete('public/images/'.$product->image);

        $imageName = time().'.'.$request->image->extension();
        $request->image->move(public_path('images'), $imageName);

        $product->update([
            'name' => $request->name,
            'description' => $request->description,
            'price' => $request->price,
            'category' => $request->category,
            'image' => $imageName
        ]);
    } else {
        $product->update([
            'name' => $request->name,
            'description' => $request->description,
            'price' => $request->price,
            'category' => $request->category,
            'image' => $request->image
        ]);
    }

    return response()->json([
        'success' => true,
        'message' => 'Product updated successfully',
        'data' => $product
    ], 200);
}

public function destroy($id)
{
    $product = Product::find($id);

    if (!$product) {
        return response()->json(['error' => 'Product not found'], 404);
    }

    Storage::delete('public/images/'. $product->image);
    $product->delete();

    return response()->json([
        'success' => true,
        'message' => 'Product deleted successfully'
    ], 200);
}

}
