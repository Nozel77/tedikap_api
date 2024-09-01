<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\RewardProduct;
use App\Models\SessionTime;
use App\Models\StatusStore;
use Carbon\Carbon;
use Illuminate\Http\Request;

class StatusStoreController extends Controller
{
    // Method untuk mengubah waktu sesi
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

    private function updateProductStock($isOpen)
    {
        Product::where('stock', ! $isOpen)->update(['stock' => $isOpen]);
        RewardProduct::where('stock', ! $isOpen)->update(['stock' => $isOpen]);
    }

    public function storeStatus()
    {
        $status = StatusStore::first();

        if (! $status) {
            return response()->json(['message' => 'Status Tidak Ada'], 404);
        }

        $now = Carbon::now('Asia/Jakarta')->format('H:i');

        $hour = Carbon::now('Asia/Jakarta')->format('H');
        $greetings = ($hour >= 5 && $hour < 12) ? 'Selamat Pagi' : (($hour >= 12 && $hour < 18) ? 'Selamat Siang' : 'Selamat Malam');

        if ($now > '14:00' && $status->open) {
            $status->open = false;
            $this->updateProductStock($status->open);
            $status->save();
        }

        if (! $status->open) {
            $session = 'Toko Sedang Tutup';
            $time = null;
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
