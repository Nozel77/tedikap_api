<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\RewardProduct;
use App\Models\StatusStore;
use Carbon\Carbon;

class StatusStoreController extends Controller
{
    private function determineSessionAndStock($now)
    {
        if ($now >= '07:00' && $now <= '09:20') {
            $session = 'Pick Up Sesi 1';
            $time = '09:40-10:00';
            $description = 'Toko Buka Untuk Sesi 1';
        } elseif ($now > '09:20' && $now <= '11:40') {
            $session = 'Pick Up Sesi 2';
            $time = '12:00-12:30';
            $description = 'Toko Buka Untuk Sesi 2';
        } else {
            $session = 'Toko Sedang Tutup';
            $time = null;
            $description = 'Toko Sedang Tutup';
        }

        return [$session, $time, $description];
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

        $now = Carbon::now('Asia/Jakarta');
        $currentTime = $now->format('H:i');

        $hour = $now->format('H');
        if ($hour >= 5 && $hour < 12) {
            $greetings = 'Selamat Pagi';
        } elseif ($hour >= 12 && $hour < 18) {
            $greetings = 'Selamat Siang';
        } else {
            $greetings = 'Selamat Malam';
        }

        if ($currentTime > '11:40' && $status->open) {
            $status->open = false;
            $this->updateProductStock($status->open);
            $status->save();
        }

        if (! $status->open) {
            $session = 'Toko Sedang Tutup';
            $time = null;
            $description = 'Toko Sedang Tutup';
        } else {
            [$session, $time, $description] = $this->determineSessionAndStock($currentTime);
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
