<?php

namespace App\Http\Controllers;

use App\Http\Resources\OrderResource;
use App\Http\Resources\OrderRewardResource;
use App\Models\Order;
use App\Models\OrderReward;

class StatisticController extends Controller
{
    public function showWeeklyStatistic()
    {

    }

    public function HistoryOrderAdmin()
    {
        $filterStatus = request()->query('status');
        $validStatuses = ['done' => 'pesanan selesai'];

        $orderQuery = Order::query();
        $orderRewardQuery = OrderReward::query();

        if (array_key_exists($filterStatus, $validStatuses)) {
            $orderQuery->where('status', $validStatuses[$filterStatus]);
            $orderRewardQuery->where('status', $validStatuses[$filterStatus]);
        } else {
            $orderQuery->whereIn('status', array_values($validStatuses));
            $orderRewardQuery->whereIn('status', array_values($validStatuses));
        }

        $orders = $orderQuery->orderBy('created_at', 'desc')->get();
        $orderRewards = $orderRewardQuery->orderBy('created_at', 'desc')->get();

        $combinedOrders = $orders->map(function ($order) {
            if ($order->order_type === 'reward order') {
                return (new OrderRewardResource($order))->toArray(request());
            } else {
                return (new OrderResource($order))->toArray(request());
            }
        });

        $combinedOrderRewards = $orderRewards->map(function ($orderReward) {
            return (new OrderRewardResource($orderReward))->toArray(request());
        });

        $allOrders = $combinedOrders->concat($combinedOrderRewards)->sortByDesc('created_at')->values()->all();

        return response()->json([
            'message' => 'Orders retrieved successfully.',
            'orders' => $allOrders,
        ]);
    }
}
