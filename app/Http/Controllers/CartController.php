<?php

namespace App\Http\Controllers;

use App\Http\Requests\CartRequest;
use App\Http\Resources\CartItemResource;
use App\Http\Resources\CartResource;
use App\Models\Cart;
use App\Models\CartItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CartController extends Controller
{
    public function showCartByUser()
    {
        $user_id = Auth::id();

        $cart = Cart::all()->where('user_id', $user_id)->first();

        if (! $cart) {
            return response()->json([
                'message' => 'Cart not found for this user.',
            ], 404);
        }
        $cart_items = CartItem::with('product')->where('cart_id', $cart->id)->get();

        $total_price = 0;
        $cart_items_array = $cart_items->map(function ($cart_items) {
            return new CartItemResource($cart_items);
        });

        foreach ($cart_items as $cart_item) {
            $total_price += $cart_item->quantity * $cart_item->price;
        }

        $cart->cartItems = $cart_items_array;
        $cart->total_price = $total_price;

        return response()->json([
            'cart' => new CartResource($cart),
        ]);
    }

    public function storeCart(CartRequest $request)
    {
        $userId = Auth::id();

        $cart = Cart::all()->where('user_id', $userId)->first();

        if ($cart != null) {
            return $this->addCartItem($cart->id, $request);
        } else {
            $cart = new Cart();
            $cart->user_id = $userId;
            $cart->save();

            return $this->addCartItem($cart->id, $request);
        }
    }

    public function addCartItem($cartId, CartRequest $request)
    {
        $data = $request->validated();

        $existingCartItem = CartItem::where('cart_id', $cartId)
            ->where('product_id', $data['product_id'])
            ->first();

        if ($existingCartItem) {
            $existingCartItem->quantity += $data['quantity'];
            $existingCartItem->save();

            return response()->json(
                [
                    'message' => 'Cart item updated successfully.',
                    'cart' => new CartItemResource($existingCartItem),
                ],
                200
            );
        } else {
            $cartItem = new CartItem();
            $cartItem->cart_id = $cartId;
            $cartItem->fill($data);
            $cartItem->note = $data['note'] ?? null;
            $cartItem->save();

            return response()->json(
                [
                    'message' => 'Cart item added successfully.',
                    'cart' => new CartItemResource($cartItem),
                ],
                201
            );
        }
    }

    public function deleteCartItem(Request $request)
    {
        $userId = Auth::id();

        $cart = Cart::all()->where('user_id', $userId)->first();

        if ($cart->cart_id) {
            return response()->json(
                [
                    'message' => 'Cart not found.',
                ],
                404
            );
        }

        $cartItem = CartItem::where('cart_id', $cart->id)
            ->where('id', $request->cart_item_id)
            ->first();

        if (! $cartItem) {
            return response()->json(
                [
                    'message' => 'Item keranjang tidak ditemukan.',
                ],
                404
            );
        }

        $cartItem->delete();

        return response()->json(
            [
                'message' => 'Cart item deleted successfully.',
            ]
        );
    }
}
