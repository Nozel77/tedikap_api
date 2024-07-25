<?php

namespace App\Http\Controllers;

use App\Http\Requests\BannerRequest;
use App\Http\Requests\BannerUpdateRequest;
use App\Http\Resources\BannerResource;
use App\Models\Banner;

class BannerController extends Controller
{
    public function index()
    {
        return BannerResource::collection(Banner::all());
    }

    public function store(BannerRequest $request)
    {
        $request->validated();

        $imageName = time().'.'.$request->file('image')->extension();
        $request->file('image')->storeAs('banner', $imageName, 'public');

        $data = new Banner([
            'image' => $imageName,
        ]);
        $data->save();

        return new BannerResource($data);
    }

    public function show($id)
    {
        $data = Banner::find($id);
        if (! $data) {
            return $this->resDataNotFound('Banner');
        }

        return new BannerResource($data);
    }

    public function update(BannerUpdateRequest $request, $id)
    {
        $request->validated();

        $data = Banner::find($id);

        if (! $data) {
            return $this->resDataNotFound('Banner');
        }

        $imageName = time().'.'.$request->file('image')->extension();
        $request->file('image')->storeAs('banner', $imageName, 'public');

        $data->image = $imageName;
        $data->save();

        return $this->resUpdatedData($data);
    }

    public function destroy($id)
    {
        $data = Banner::find($id);

        if (! $data) {
            return $this->resDataNotFound('Banner');
        }

        $data->delete();

        return $this->resDataDeleted();
    }
}
