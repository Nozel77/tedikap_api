<?php

namespace App\Http\Controllers;

use App\Http\Requests\OrderRequest;
use App\Http\Resources\OrderResource;
use App\Models\Cart;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Voucher;
use Illuminate\Support\Facades\Auth;

class OrderController extends Controller
{
    public function index()
    {
        $data = Order::all();

        return $this->resShowData($data);
    }

    public function store(OrderRequest $request)
    {
        $userId = Auth::id();
        $data = $request->validated();

        $cart = Cart::where('id', $data['cart_id'])
            ->where('user_id', $userId)
            ->with('cartItems')
            ->first();

        if (! $cart) {
            return response()->json([
                'message' => 'Cart not found or does not belong to the authenticated user.',
            ], 404);
        }

        $totalPrice = $cart->cartItems->sum(function ($cartItem) {
            return $cartItem->quantity * $cartItem->price;
        });

        $discountAmount = 0;
        if (isset($data['voucher_id'])) {
            $voucher = Voucher::find($data['voucher_id']);
            if ($voucher) {
                $discountPercentage = $voucher->discount;
                $discountAmount = ($discountPercentage / 100) * $totalPrice;
                $totalPrice -= $discountAmount;
            }
        }

        if ($totalPrice < 0) {
            $totalPrice = 0;
        }

        $order = new Order();
        $order->user_id = $userId;
        $order->cart_id = $cart->id;
        $order->voucher_id = $data['voucher_id'] ?? null;
        $order->total_price = $totalPrice;
        $order->discount_amount = $discountAmount;
        $order->status = 'ongoing';

        $order->save();

        foreach ($cart->cartItems as $cartItem) {
            $orderItem = new OrderItem();
            $orderItem->order_id = $order->id;
            $orderItem->product_id = $cartItem->product_id;
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
}
