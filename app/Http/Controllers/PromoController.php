<?php

namespace App\Http\Controllers;

use App\Http\Requests\PromoRequest;
use App\Http\Resources\PromoResource;
use App\Models\Promo;
use Illuminate\Support\Facades\Storage;

class PromoController extends Controller
{
    public function index()
    {
        $data = Promo::all();

        return PromoResource::collection($data);
    }

    public function store(PromoRequest $request)
    {
        $request->validated();

        $imageName = time().'.'.$request->file('image')->extension();
        $request->image->storeAs('promo', $imageName, 'public');

        $data = new Promo([
            'title' => $request->title,
            'description' => $request->description,
            'image' => $imageName,
            'value' => $request->value,
            'min_transaction' => $request->min_transaction,
            'expired' => $request->expired,
        ]);
        $data->save();

        return new PromoResource($data);
    }

    public function show($id)
    {
        $data = Promo::find($id);
        if (! $data) {
            return $this->resDataNotFound('Promo');      }

        return new PromoResource($data);
    }

    public function update(PromoRequest $request, $id)
    {
        $request->validated();

        $data = Promo::find($id);

        if (! $data) {
            return $this->resDataNotFound('Promo');
        }

        if ($request->hasFile('image')) {
            Storage::delete('public/promo/'.$data->image);

            $imageName = time().'.'.$request->image->extension();
            $request->file('image')->storeAs('promo', $imageName, 'public');

            $data->update([
                'title' => $request->title,
                'description' => $request->description,
                'image' => $request->image,
                'value' => $request->value,
                'min_transaction' => $request->min_transaction,
                'expired' => $request->expired,
            ]);
        } else {
            $data->update([
                'title' => $request->title,
                'description' => $request->description,
                'value' => $request->value,
                'min_transaction' => $request->min_transaction,
                'expired' => $request->expired,
            ]);
        }

        return $this->resUpdatedData($data);
    }

    public function destroy($id)
    {
        $data = Promo::find($id);

        if (! $data) {
            return $this->resDataNotFound('Promo');
        }

        Storage::delete('public/images/'.$data->image);
        $data->delete();

        return $this->resDataDeleted();
    }
}
