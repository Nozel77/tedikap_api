<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\RewardProduct;
use App\Models\SessionTime;
use App\Models\StatusStore;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StatusStoreController extends Controller
{
    public function storeStatus() : JsonResponse
{
    $status = StatusStore::all()->first();

    //error handling jika status tidak ada
    if (! $status) {
        return response()->json(['message' => 'Status Tidak Ada'], 404);
    }

    // mengambil waktu sekarang
    $now = Carbon::now('Asia/Jakarta')->format('H:i');

    // menentukan waktu pagi, siang, atau malam
    $hour = Carbon::now('Asia/Jakarta')->format('H');
    $greetings = ($hour >= 5 && $hour < 12) ? 'Selamat Pagi' : (($hour >= 12 && $hour < 18) ? 'Selamat Siang' : 'Selamat Malam');

    // buka toko otomatis ketika jam 07.00
    if ($now >= '07:00' && ! $status->open) {
        $status->open = true;
        $status->save();
    }

    // tutup toko otomatis jam 16:00
    if ($now > '17:00' && $status->open) {
        $status->open = false;
        $this->updateProductStock($status->open);
        $status->save();
    }

    // jika toko tutup
    if (! $status->open) {
        $session = 'Toko Sedang Tutup';
        $time = 'Toko akan buka besok';
        $description = 'Toko Sedang Tutup';
    } else {
        [$session, $time, $description] = $this->determineSessionAndStock($now);
    }

    $this->updateProductStock($status->open);

    return response()->json([
        'data' => [
            'status_store' => $status->open,
            'description' => $description,
            'session' => $session,
            'time' => $time,
            'greetings' => $greetings,
        ],
    ]);
}
    public function getSessionTimes()
    {
        $sessionTimes = SessionTime::all();

        return response()->json(['session_times' => $sessionTimes], 200);
    }
    public function updateSessionTimes(Request $request)
    {
        $request->validate([
            'session_times' => 'required|array',
            'session_times.*.id' => 'required|exists:session_times,id',
            'session_times.*.start_time' => 'required|date_format:H:i',
            'session_times.*.end_time' => 'required|date_format:H:i',
        ]);

        foreach ($request->session_times as $session) {
            $sessionTime = SessionTime::find($session['id']);
            $sessionTime->start_time = $session['start_time'];
            $sessionTime->end_time = $session['end_time'];
            $sessionTime->save();
        }

        return response()->json(['message' => 'Waktu sesi berhasil diubah'], 200);
    }
    public function determineSessionAndStock($now) : array
    {
        $sessionTimes = SessionTime::all();

        foreach ($sessionTimes as $session) {
            $startTimeFormatted = Carbon::parse($session->start_time)->format('H:i');
            $endTimeFormatted = Carbon::parse($session->end_time)->format('H:i');

            if ($now >= $startTimeFormatted && $now <= $endTimeFormatted) {
                return [
                    $session->session_name,
                    "{$startTimeFormatted}-{$endTimeFormatted}",
                    "Toko Buka Untuk {$session->session_name}",
                ];
            }
        }

        return ['Toko Sedang Tutup', 'Toko Sedang Tutup', 'Toko Sedang Tutup'];
    }
    private function updateProductStock($isOpen) : void
    {
        Product::where('stock', ! $isOpen)->update(['stock' => $isOpen]);
        RewardProduct::where('stock', ! $isOpen)->update(['stock' => $isOpen]);
    }
    public function updateStoreStatus()
    {
        $status = StatusStore::first();

        if (! $status) {
            return response()->json(['message' => 'Status Tidak Ada'], 404);
        }

        $status->open = ! $status->open;
        $status->save();

        $now = Carbon::now('Asia/Jakarta')->format('H:i');

        if ($status->open) {
            [$session, $time, $description] = $this->determineSessionAndStock($now);
        } else {
            $session = 'Toko Sedang Tutup';
            $time = null;
            $description = 'Toko Sedang Tutup';
        }

        $this->updateProductStock($status->open);

        return response()->json([
            'message' => 'Status Toko Berhasil Diubah',
            'data' => [
                'status_store' => $status->open,
                'description' => $description,
                'session' => $session,
                'time' => $time,
            ],
        ], 200);
    }
}
