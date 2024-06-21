<?php

namespace App\Http\Controllers;

use App\Models\StatusStore;
use Illuminate\Http\Request;

class StatusStoreController extends Controller
{
    public function storeStatus(){
        $status = StatusStore::all()->first();
        return response()->json([
            'status_store' => $status->open,
            'description' => $status->open ? 'The store is open' : 'The store is closed'
        ]);
    }

    public function updateStoreStatus(){
        $status = StatusStore::all()->first();
        $status->open = !$status->open;
        $status->save();
        return response()->json([
            'message' => 'Store status updated successfully'
        ]);
    }
}
