<?php

namespace App\Http\Controllers;

use App\Http\Requests\CartRewardItemRequest;
use App\Http\Requests\CartRewardRequest;
use App\Http\Resources\CartRewardItemResource;
use App\Http\Resources\CartRewardResource;
use App\Models\CartReward;
use App\Models\CartRewardItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CartRewardController extends Controller
{
    public function showCartByUser()
    {
        $user_id = Auth::id();

        $cart = CartReward::where('user_id', $user_id)->first();
        if (! $cart) {
            return response()->json([
                'message' => 'Cart not found for this user.',
            ], 404);
        }

        $cart_items = CartRewardItem::where('cart_reward_id', $cart->id)->get();
        $total_points = $cart_items->sum(function ($cart_item) {
            return $cart_item->quantity * $cart_item->points;
        });

        $cart_items_array = $cart_items->map(function ($cart_item) {
            return new CartRewardItemResource($cart_item);
        });

        $cart->cartItems = $cart_items_array;
        $cart->total_points = $total_points;

        return response()->json([
            'cart' => new CartRewardResource($cart),
        ]);
    }

    public function showCartItemById($cartItemId)
    {
        $user_id = Auth::id();

        $cartItem = CartRewardItem::whereHas('cartReward', function ($query) use ($user_id) {
            $query->where('user_id', $user_id);
        })->where('id', $cartItemId)->with('rewardProduct')->first();

        if (! $cartItem) {
            return response()->json([
                'message' => 'CartReward item not found for this user.',
            ], 404);
        }

        return response()->json([
            'cart_item' => new CartRewardItemResource($cartItem),
        ]);
    }

    public function storeCart(CartRewardRequest $request)
    {
        $userId = Auth::id();

        $cart = CartReward::where('user_id', $userId)->first();

        if ($cart) {
            return $this->addCartItem($cart->id, $request);
        } else {
            $cart = new CartReward();
            $cart->user_id = $userId;
            $cart->save();

            return $this->addCartItem($cart->id, $request);
        }
    }

    public function addCartItem($cartId, CartRewardRequest $request)
    {
        $data = $request->validated();

        $existingCartRewardItem = CartRewardItem::where('cart_reward_id', $cartId)
            ->where('reward_product_id', $data['reward_product_id'])
            ->where('size', $data['size'])
            ->where('temperatur', $data['temperatur'])
            ->where('sugar', $data['sugar'])
            ->where('ice', $data['ice'])
            ->first();

        if ($existingCartRewardItem) {
            $existingCartRewardItem->quantity += $data['quantity'];
            $existingCartRewardItem->save();

            return response()->json([
                'message' => 'Reward Cart item updated successfully.',
                'cart' => new CartRewardItemResource($existingCartRewardItem),
            ], 200);
        } else {
            $cartRewardItem = new CartRewardItem();
            $cartRewardItem->cart_reward_id = $cartId;
            $cartRewardItem->fill($data);
            $cartRewardItem->note = $data['note'] ?? null;
            $cartRewardItem->save();

            return response()->json([
                'message' => 'Reward Cart item added successfully.',
                'cart' => new CartRewardItemResource($cartRewardItem),
            ], 201);
        }
    }

    public function updateCartItem($cartItemId, CartRewardItemRequest $request)
    {
        $user_id = Auth::id();

        $cartItem = CartRewardItem::whereHas('cart', function ($query) use ($user_id) {
            $query->where('user_id', $user_id);
        })->where('id', $cartItemId)->first();

        if (! $cartItem) {
            return response()->json([
                'message' => 'Cart item not found for this user.',
            ], 404);
        }

        $data = $request->validated();

        $cartItem->fill($data);
        $cartItem->note = $data['note'] ?? $cartItem->note;
        $cartItem->save();

        return response()->json([
            'message' => 'Reward Cart item updated successfully.',
            'cart' => new CartRewardItemResource($cartItem),
        ]);
    }

    public function updateCartItemQuantity($cartItemId, Request $request)
    {
        $cartItem = CartRewardItem::findOrFail($cartItemId);

        if (! $request->has('action')) {
            return response()->json([
                'message' => 'Action parameter is required.',
            ], 400);
        }

        $action = $request->input('action');

        if ($action === 'increment') {
            $cartItem->quantity++;
        } elseif ($action === 'decrement') {
            if ($cartItem->quantity > 1) {
                $cartItem->quantity--;
            } else {
                return response()->json([
                    'message' => 'Minimum quantity reached.',
                ], 400);
            }
        } else {
            return response()->json([
                'message' => 'Invalid action parameter.',
            ], 400);
        }

        $cartItem->save();

        return response()->json([
            'message' => 'Reward Cart item quantity updated successfully.',
            'cart_item' => new CartRewardItemResource($cartItem),
        ]);
    }

    public function deleteCartItem($cartItemId)
    {
        $userId = Auth::id();

        $cartItem = CartRewardItem::whereHas('cart', function ($query) use ($userId) {
            $query->where('user_id', $userId);
        })->where('id', $cartItemId)->first();

        if (! $cartItem) {
            return response()->json([
                'message' => 'Cart item not found for this user.',
            ], 404);
        }

        $cartItem->delete();

        return response()->json([
            'message' => 'Cart item deleted successfully.',
        ]);
    }
}
