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
    public function index()
    {
        $user = Auth::user();

        $orders = Order::where('user_id', $user->id)->get();

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

        $order = new Order();
        $order->user_id = $userId;
        $order->cart_id = $cart->id;
        $order->voucher_id = $cart->voucher_id;
        $order->total_price = $totalPrice;
        $order->discount_amount = $discountAmount;
        $order->status = 'ongoing';
        $order->save();

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

    return response()->json([
        'message' => 'Order retrieved successfully.',
        'order' => new OrderResource($order),
    ]);
}

}
