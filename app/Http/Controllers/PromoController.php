<?php

namespace App\Http\Controllers;

use App\Http\Requests\PromoRequest;
use App\Models\Promo;
use Illuminate\Support\Facades\Storage;

class PromoController extends Controller
{
    public function index()
    {
        $data = Promo::all();

        return response()->json([
            'status' => 'success',
            'message' => 'get data success',
            'data' => $data,
        ]);
    }

    public function store(PromoRequest $request)
    {
        $request->validated();

        $imageName = time().'.'.$request->image->extension();
        $request->image->move(public_path('images'), $imageName);

        $promo = new Promo([
            'title' => $request->title,
            'description' => $request->description,
            'image' => $request->image,
            'value' => $request->value,
            'min_transaction' => $request->min_transaction,
            'expired' => $request->expired,
        ]);
        $promo->save();

        return response()->json([
            'success' => true,
            'message' => 'Promo created successfully',
            'data' => $promo,
        ], 201);
    }

    public function show($id)
    {
        $data = Promo::find($id);
        if (! $data) {
            return response()->json([
                'status' => 'error',
                'message' => 'Promo not found',
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'get data successfully',
            'data' => $data,
        ]);
    }

    public function update(PromoRequest $request, $id)
    {
        $request->validated();

        $promo = Promo::find($id);

        if (! $promo) {
            return response()->json(['error' => 'Promo not found'], 404);
        }

        if ($request->hasFile('image')) {
            Storage::delete('public/images/'.$promo->image);

            $imageName = time().'.'.$request->image->extension();
            $request->image->move(public_path('images'), $imageName);

            $promo->update([
                'title' => $request->title,
                'description' => $request->description,
                'image' => $request->image,
                'value' => $request->value,
                'min_transaction' => $request->min_transaction,
                'expired' => $request->expired,
            ]);
        } else {
            $promo->update([
                'title' => $request->title,
                'description' => $request->description,
                'value' => $request->value,
                'min_transaction' => $request->min_transaction,
                'expired' => $request->expired,
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Promo updated successfully',
            'data' => $promo,
        ], 200);
    }

    public function destroy($id)
    {
        $promo = Promo::find($id);

        if (! $promo) {
            return response()->json(['error' => 'Promo not found'], 404);
        }

        Storage::delete('public/images/'.$promo->image);
        $promo->delete();

        return response()->json([
            'success' => true,
            'message' => 'Promo deleted successfully',
        ]);
    }
}
