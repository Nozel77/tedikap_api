<?php

namespace App\Http\Controllers;

use App\Http\Requests\ReviewRequest;
use App\Http\Resources\ReviewResource;
use App\Models\Order;
use App\Models\OrderReward;
use App\Models\Review;
use Illuminate\Http\Request;
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
        $review->rating = ($data['staff_service'] + $data['product_quality']) / 2; // Menyimpan rating
        $review->save();

        $order->rating = $review->rating;
        $order->save();

        return response()->json([
            'message' => 'Review berhasil disimpan.',
            'data' => new ReviewResource($review),
        ], 201);
    }

    public function index(Request $request)
    {
        $ratingFilter = $request->query('rating');

        $query = Review::orderBy('created_at', 'desc');

        if ($ratingFilter !== null) {
            $ratingFilter = (float) $ratingFilter;
            if ($ratingFilter < 0 || $ratingFilter > 5) {
                return response()->json([
                    'message' => 'isi antara 0 dan 5.',
                ], 400);
            }

            $roundedRating = floor($ratingFilter);

            $query->whereRaw('FLOOR(rating) = ?', [$roundedRating]);
        }

        $reviews = $query->get();

        return response()->json([
            'message' => 'Berhasil menampilkan data review.',
            'data' => ReviewResource::collection($reviews),
        ]);
    }
}
