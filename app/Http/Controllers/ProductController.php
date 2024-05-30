<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProductRequest;
use App\Http\Resources\ProductResource;
use App\Models\Product;
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
            'price' => $request->price,
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

    public function update(ProductRequest $request, $id)
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
                'price' => $request->price,
                'category' => $request->category,
                'image' => $imageName,
            ]);
        } else {
            $data->update([
                'name' => $request->name,
                'description' => $request->description,
                'price' => $request->price,
                'category' => $request->category,
                'image' => $request->image,
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
}
