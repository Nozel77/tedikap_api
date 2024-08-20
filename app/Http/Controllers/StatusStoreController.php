<?php

namespace App\Http\Controllers;

use App\Models\StatusStore;
use Carbon\Carbon;

class StatusStoreController extends Controller
{
    public function storeStatus()
    {
        $status = StatusStore::first();

        if (! $status) {
            return response()->json(['message' => 'Status store not found'], 404);
        }

        $now = Carbon::now('Asia/Jakarta')->format('H:i');

        if (! $status->open) {
            $session = 'CLOSED';
            $time = null;
            $description = 'The store is closed';
        } else {
            if ($now >= '07:00' && $now <= '09:20') {
                $session = 'Pick Up Sesi 1';
                $time = '09:40-10:00';
                $description = 'The store is open for Sesi 1';
            } elseif ($now > '09:20' && $now <= '11:40') {
                $session = 'Pick Up Sesi 2';
                $time = '12:00-12:30';
                $description = 'The store is open for Sesi 2';
            } else {
                $session = 'CLOSED';
                $time = null;
                $description = 'The store is closed';
            }
        }

        return response()->json([
            'data' => [
                'status_store' => $status->open,
                'description' => $description,
                'session' => $session,
                'time' => $time,
            ],
        ]);
    }

    public function updateStoreStatus()
    {
        $status = StatusStore::first();

        if (! $status) {
            return response()->json(['message' => 'Status store not found'], 404);
        }

        $status->open = ! $status->open;
        $status->save();

        $now = Carbon::now('Asia/Jakarta')->format('H:i');

        if ($status->open) {
            if ($now <= '09:20') {
                $session = 'Pick Up Sesi 1';
                $time = '09:40-10:00';
            } elseif ($now > '09:20' && $now <= '11:40') {
                $session = 'Pick Up Sesi 2';
                $time = '12:00-12:30';
            } else {
                $session = 'CLOSED';
                $time = null;
            }
        } else {
            $session = 'CLOSED';
            $time = null;
        }

        return response()->json([
            'message' => 'Store status updated successfully',
            'data' => [
                'status_store' => $status->open,
                'description' => $status->open ? 'The store is open' : 'The store is closed',
                'session' => $session,
                'time' => $time,
            ],
        ], 200);
    }
}
