<?php

namespace App\Http\Controllers;

use App\Models\SessionTime;
use App\Models\StatusStore;
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

    public function getSchedulePickup()
    {
        $now = Carbon::now('Asia/Jakarta')->format('H:i');

        if ($now >= '07:00' && $now <= '09:20') {
            return '09:40-10:00';
        } elseif ($now > '09:20' && $now <= '11:40') {
            return '12:00-12:30';
        } else {
            return 'Toko Sedang Tutup :)';
        }
    }

    public function getStoreStatus()
    {
        $status = StatusStore::first();

        if (! $status) {
            return [
                'status_store' => false,
                'description' => 'Status Tidak Ada',
                'session' => 'Toko Sedang Tutup',
                'time' => null,
                'greetings' => 'Selamat Malam',
            ];
        }

        $now = Carbon::now('Asia/Jakarta')->format('H:i');
        $hour = Carbon::now('Asia/Jakarta')->format('H');
        $greetings = ($hour >= 5 && $hour < 12) ? 'Selamat Pagi' : (($hour >= 12 && $hour < 18) ? 'Selamat Siang' : 'Selamat Malam');

        if ($now > '14:00' && $status->open) {
            $status->open = false;
            $status->save();
        }

        $session = 'Toko Sedang Tutup';
        $time = null;
        $description = 'Toko Sedang Tutup';

        if ($status->open) {
            [$session, $time, $description] = $this->determineSessionAndStock($now);
        }

        return [
            'status_store' => $status->open,
            'description' => $description,
            'session' => $session,
            'time' => $time,
            'greetings' => $greetings,
        ];
    }

    private function determineSessionAndStock($now)
    {
        $sessionTimes = SessionTime::all();

        foreach ($sessionTimes as $session) {
            if ($now >= $session->start_time && $now <= $session->end_time) {
                return [
                    $session->session_name,
                    "{$session->start_time}-{$session->end_time}",
                    "Toko Buka Untuk {$session->session_name}",
                ];
            }
        }

        return ['Toko Sedang Tutup', null, 'Toko Sedang Tutup'];
    }
}
