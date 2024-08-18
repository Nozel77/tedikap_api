<?php

namespace App\Http\Controllers;

use App\Http\Requests\ReviewRequest;
use App\Http\Resources\ReviewResource;
use App\Models\Order;
use App\Models\OrderReward;
use App\Models\Review;
use Illuminate\Support\Facades\Auth;

class ReviewController extends Controller
{
    public function store(ReviewRequest $request, $orderId)
    {
        $user = Auth::user();
        $data = $request->validated();

        $order = Order::where('id', $orderId)->where('user_id', $user->id)->first();

        if ($order) {
            $orderType = 'order';
        } else {
            $order = OrderReward::where('id', $orderId)->where('user_id', $user->id)->first();

            if (! $order) {
                return response()->json([
                    'message' => 'Order tidak ditemukan atau tidak milik pengguna.',
                ], 404);
            }

            $orderType = 'reward order';
        }

        $review = new Review();
        $review->user_id = $user->id;
        $review->order_id = $order->id;
        $review->staff_service = $data['staff_service'];
        $review->product_quality = $data['product_quality'];
        $review->note = $data['note'] ?? null;
        $review->save();

        $averageRating = ($data['staff_service'] + $data['product_quality']) / 2;

        if ($orderType === 'order') {
            $order->rating = $averageRating;
        } elseif ($orderType === 'reward order') {
            $order->rating = $averageRating;
        }

        $order->save();

        return response()->json([
            'message' => 'Review berhasil disimpan.',
            'data' => new ReviewResource($review),
        ], 201);
    }

    public function index()
    {
        $review = Review::orderBy('created_at', 'desc')->get();

        return response()->json([
            'message' => 'Berhasil menampilkan data review.',
            'data' => ReviewResource::collection($review),
        ]);
    }
}
