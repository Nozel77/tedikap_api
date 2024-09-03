<?php

namespace App\Http\Controllers;

use App\Http\Resources\PointConfigurationResource;
use App\Models\PointConfiguration;
use Illuminate\Http\Request;

class PointConfigurationController extends Controller
{
    public function index()
    {
        $data = PointConfiguration::all()->first();

        return new PointConfigurationResource($data);
    }

    public function update(Request $request, $id)
    {
        $data = $request->validate([
            'minimum_amount' => 'required|integer',
            'collect_point' => 'required|integer',
        ]);

        $config = PointConfiguration::all()->find($id);
        $config->fill($data);

        $config->save();

        return $this->resUpdatedData($config);
    }
}
