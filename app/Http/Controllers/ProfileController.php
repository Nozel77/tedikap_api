<?php

namespace App\Http\Controllers;

use App\Http\Resources\UserResource;
use App\Models\User;

class ProfileController extends Controller
{
    public function index($id)
    {
        $data = User::where('id', $id)->first();

        if (! $data) {
            return $this->resDataNotFound('User');
        }

        return new UserResource($data);
    }
}
