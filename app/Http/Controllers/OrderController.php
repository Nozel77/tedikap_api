<?php

namespace App\Http\Controllers;

use App\Http\Requests\OrderRequest;
use App\Http\Resources\OrderResource;
use App\Models\Cart;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\UserVoucher;
use App\Models\Voucher;
use Illuminate\Support\Facades\Auth;

class OrderController extends Controller
{
    public function generateCustomUUID()
    {
        $now = now()->setTimezone('Asia/Jakarta');
        $date = $now->format('d');
        $month = $now->format('m');
        $year = $now->format('Y');
        $hour = $now->format('H');
        $minute = $now->format('i');
        $second = $now->format('s');
        $customUUID = strtoupper("ORD{$date}{$month}{$year}{$hour}{$minute}{$second}");

        return $customUUID;
    }

    public function index()
    {
        $user = Auth::user();

        $orders = Order::where('user_id', $user->id)->get();

        $orders = $orders->map(function ($order) {
            $createdAt = $order->created_at->setTimezone('Asia/Jakarta');
            $time = $createdAt->format('H:i');

            if ($time <= '09:20') {
                $pickupTime = '09:40-10:00';
            } elseif ($time > '09:20' && $time <= '11:40') {
                $pickupTime = '12:00-12:30';
            } else {
                $pickupTime = 'CLOSED';
            }

            $order->schedule_pickup = $pickupTime;

            return $order;
        });

        return response()->json([
            'message' => 'Orders retrieved successfully.',
            'orders' => OrderResource::collection($orders),
        ]);
    }

    public function store(OrderRequest $request)
    {
        $userId = Auth::id();
        $data = $request->validated();

        $cart = Cart::where('user_id', $userId)
            ->with('cartItems')
            ->first();

        if (! $cart) {
            return response()->json([
                'message' => 'Cart not found for the authenticated user.',
            ], 404);
        }

        $totalPrice = $cart->cartItems->sum(function ($cartItem) {
            return $cartItem->quantity * $cartItem->price;
        });

        $discountAmount = 0;
        if ($cart->voucher_id) {
            $voucher = Voucher::find($cart->voucher_id);
            if ($voucher) {
                $discountPercentage = $voucher->discount;
                $discountAmount = ($discountPercentage / 100) * $totalPrice;
                $totalPrice -= $discountAmount;
                $userVoucher = UserVoucher::firstOrNew([
                    'user_id' => $userId,
                    'voucher_id' => $cart->voucher_id,
                ]);
                $userVoucher->used = true;
                $userVoucher->save();
            }
        }

        $additionalPoints = floor($totalPrice / 3000);
        $additionalPoints += ($totalPrice % 3000 == 0) ? 0 : 1;

        $order = new Order();
        $order->id = $this->generateCustomUUID();
        $order->user_id = $userId;
        $order->cart_id = $cart->id;
        $order->voucher_id = $cart->voucher_id;
        $order->total_price = $totalPrice;
        $order->discount_amount = $discountAmount;
        $order->reward_point = $additionalPoints;
        $order->status = 'ongoing';
        $order->save();

        $createdAt = $order->created_at->setTimezone('Asia/Jakarta');
        $time = $createdAt->format('H:i');

        if ($time <= '09:20') {
            $order->schedule_pickup = '09:40-10:00';
        } elseif ($time > '09:20' && $time <= '11:40') {
            $order->schedule_pickup = '12:00-12:30';
        } else {
            $pickupTime = 'CLOSED';
        }

        $order->schedule_pickup = $pickupTime;

        $cart->voucher_id = null;
        $cart->save();

        foreach ($cart->cartItems as $cartItem) {
            $orderItem = new OrderItem();
            $orderItem->order_id = $order->id;
            $orderItem->product_id = $cartItem->product_id;
            $orderItem->temperatur = $cartItem->temperatur;
            $orderItem->size = $cartItem->size;
            $orderItem->ice = $cartItem->ice;
            $orderItem->sugar = $cartItem->sugar;
            $orderItem->note = $cartItem->note;
            $orderItem->quantity = $cartItem->quantity;
            $orderItem->price = $cartItem->price;
            $orderItem->save();
        }

        $cart->cartItems()->delete();

        return response()->json([
            'message' => 'Order placed successfully.',
            'order' => new OrderResource($order),
        ], 201);
    }

    public function show($id)
    {
        $user = Auth::user();

        $order = Order::where('id', $id)->where('user_id', $user->id)->first();

        if (! $order) {
            return response()->json([
                'message' => 'Order not found.',
            ], 404);
        }

        if (! $order->schedule_pickup) {
            $createdAt = $order->created_at->setTimezone('Asia/Jakarta');
            $time = $createdAt->format('H:i');

            if ($time <= '09:20') {
                $order->schedule_pickup = '09:40-10:00';
            } elseif ($time > '09:20' && $time <= '11:40') {
                $order->schedule_pickup = '12:00-12:30';
            } else {
                $pickupTime = 'CLOSED';
            }

            $order->schedule_pickup = $pickupTime;
        }

        return response()->json([
            'message' => 'Order retrieved successfully.',
            'order' => new OrderResource($order),
        ]);
    }
}
