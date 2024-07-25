<?php

namespace App\Http\Controllers;

use App\Http\Requests\BoxPromoRequest;
use App\Http\Requests\BoxPromoUpdateRequest;
use App\Http\Resources\BoxPromoResource;
use App\Models\BoxPromo;
use Illuminate\Support\Facades\Storage;

class BoxPromoController extends Controller
{
    public function index()
    {
        return BoxPromoResource::collection(BoxPromo::all());
    }

    public function store(BoxPromoRequest $request)
    {
        $request->validated();

        $imageName = time().'.'.$request->file('image')->extension();
        $request->file('image')->storeAs('box-promo', $imageName, 'public');

        $data = new BoxPromo([
            'title' => $request->title,
            'subtitle' => $request->subtitle,
            'image' => $imageName,
        ]);
        $data->save();

        return new BoxPromoResource($data);
    }

    public function show($id)
    {
        $data = BoxPromo::find($id);
        if (! $data) {
            return $this->resDataNotFound('Box Promo');
        }

        return new BoxPromoResource($data);
    }

    public function update(BoxPromoUpdateRequest $request, $id)
    {

        $request->validated();

        $data = BoxPromo::find($id);

        if (! $data) {
            return $this->resDataNotFound('Box Promo');
        }

        if ($request->hasFile('image')) {
            Storage::delete('public/box-promo/'.$data->image);

            $imageName = time().'.'.$request->image->extension();
            $request->file('image')->storeAs('box-promo', $imageName, 'public');

            $data->update([
                'title' => $request->title,
                'subtitle' => $request->subtitle,
                'image' => $imageName,
            ]);
        } else {
            $data->update([
                'title' => $request->title,
                'subtitle' => $request->subtitle,
            ]);
        }

        return $this->resUpdatedData($data);
    }

    public function destroy($id)
    {
        $data = BoxPromo::find($id);
        if (! $data) {
            return $this->resDataNotFound('Box Promo');
        }

        Storage::delete('public/box-promo/'.$data->image);
        $data->delete();

        return $this->resDataDeleted($data);
    }
}
