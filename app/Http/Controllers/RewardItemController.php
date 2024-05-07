<?php

namespace App\Http\Controllers;

use App\Http\Requests\RewardItemRequest;
use App\Models\RewardItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class RewardItemController extends Controller
{
    public function index(){
        $rewardItems = RewardItem::all();
        return response()->json([
            'status' => 'success',
            'data' => $rewardItems
        ]);
    }

    public function store(RewardItemRequest $request){
        $request->validated();

        $rewardItem = new RewardItem([
            'user_id' => $request->user_id,
            'reward_product_id' => $request->reward_product_id
        ]);
        $rewardItem->save();

        return response()->json([
            'success' => true,
            'message' => 'reward item created successfully',
            'data' => $rewardItem
        ], 201);
    }

    // public function update(RewardItemRequest $request, $id){
    //     $request->validated();

    //     $rewardItem = RewardItem::find($id);

    //     if (!$rewardItem) {
    //         return response()->json([
    //             'status' => 'error',
    //             'message' => 'Reward item not found'
    //         ], 404);
    //     }
        
    //     $rewardItem->update([
    //         'user_id' => $request->user_id,
    //         'reward_product_id' => $request->reward_product_id
    //     ]);
    //     $rewardItem->save();
        
    //     return response()->json([
    //         'status' => 'success',
    //         'message' => 'Reward Item updated successfully',
    //         'data' => $rewardItem
    //     ]);
    // }

    public function destroy($id){
        $rewardItem = RewardItem::find($id);

        if (!$rewardItem) {
            return response()->json([
                'status' => 'error',
                'message' => 'Reward item not found'
            ], 404);
        }

        $rewardItem->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Reward item deleted successfully'
        ]);
    }
}
