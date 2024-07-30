<?php

namespace App\Http\Controllers;

use App\Http\Requests\HelpCenterRequest;
use App\Http\Requests\HelpCenterUpdateRequest;
use App\Http\Resources\HelpCenterResource;
use App\Models\HelpCenter;

class HelpCenterController extends Controller
{
    public function index()
    {
        return HelpCenterResource::collection(HelpCenter::all());
    }

    public function store(HelpCenterRequest $request)
    {
        $request->validated();

        $data = new HelpCenter([
            'title' => $request->title,
            'subtitle' => $request->subtitle,
        ]);
        $data->save();

        return new HelpCenterResource($data);
    }

    public function update(HelpCenterUpdateRequest $request, $id)
    {
        $request->validated();

        $data = HelpCenter::find($id);

        if (! $data) {
            return $this->resDataNotFound('Help Center');
        }

        $data->update([
            'title' => $request->title,
            'subtitle' => $request->subtitle,
        ]);

        return $this->resUpdatedData($data);
    }

    public function destroy($id)
    {
        $data = HelpCenter::find($id);

        if (! $data) {
            return $this->resDataNotFound('Help Center');
        }

        $data->delete();

        return $this->resDataDeleted();
    }
}
