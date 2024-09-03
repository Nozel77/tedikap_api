<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

class Controller extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;

    public function resShowData($data)
    {
        return response(['data' => $data], 200);
    }

    public function resInvalidLogin()
    {
        return response(['message' => 'Email or Password Is Invalid'], 409);
    }

    public function resUpdatedData($data)
    {
        return response([
            'message' => 'Data Updated',
            'data' => $data,
        ], 200);
    }

    public function resAddData($data)
    {
        return response([
            'message' => 'Data Added',
            'data' => $data,
        ], 201);
    }

    public function resUserNotFound()
    {
        return response(['message' => 'User Not Found'], 404);
    }

    public function resUserNotAdmin()
    {
        return response(['message' => 'User Not Admin'], 403);
    }

    public function resDataNotFound($data)
    {
        return response(['message' => $data.' Not Found'], 404);
    }

    public function resDataDeleted()
    {
        return response(['message' => 'Data Deleted'], 200);
    }

    //    public function getSchedulePickup()
    //    {
    //        $now = Carbon::now('Asia/Jakarta')->format('H:i');
    //
    //        if ($now >= '07:00' && $now <= '09:20') {
    //            return '09:40-10:00';
    //        } elseif ($now > '09:20' && $now <= '11:40') {
    //            return '12:00-12:30';
    //        } else {
    //            return 'Toko Sedang Tutup :)';
    //        }
    //    }
}
