<?php

namespace App\Http\Controllers;

use App\Http\Requests\ApplyVoucherRequest;
use App\Http\Requests\CartItemRequest;
use App\Http\Requests\CartRequest;
use App\Http\Resources\CartItemResource;
use App\Http\Resources\CartResource;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use App\Models\UserVoucher;
use App\Models\Voucher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CartController extends Controller
{
    public function showCartByUser()
    {
        $user_id = Auth::id();

        $cart = Cart::where('user_id', $user_id)->first();

        if (! $cart) {
            return response()->json([
                'message' => 'Cart not found for this user.',
            ], 404);
        }

        $cart_items = CartItem::with('product')->where('cart_id', $cart->id)->get();

        $total_price = $cart_items->sum(function ($cart_item) {
            return $cart_item->quantity * $cart_item->price;
        });

        $discount_amount = 0;
        if ($cart->voucher_id) {
            $voucher = Voucher::find($cart->voucher_id);
            if ($voucher) {
                $discount_percentage = $voucher->discount;
                $discount_amount = ($discount_percentage / 100) * $total_price;
            }
        }

        $total_price_after_discount = $total_price - $discount_amount;
        $original_price = $total_price;

        $cart_items_array = $cart_items->map(function ($cart_item) {
            return new CartItemResource($cart_item);
        });

        $cart->cartItems = $cart_items_array;
        $cart->total_price = max(0, $total_price_after_discount);
        $cart->discount_amount = $discount_amount;
        $cart->original_price = $original_price;

        return response()->json([
            'cart' => new CartResource($cart),
        ]);
    }

    public function showCartItemById($cartItemId)
    {
        $user_id = Auth::id();

        $cartItem = CartItem::whereHas('cart', function ($query) use ($user_id) {
            $query->where('user_id', $user_id);
        })->where('id', $cartItemId)->with('product')->first();

        if (! $cartItem) {
            return response()->json([
                'message' => 'Cart item not found for this user.',
            ], 404);
        }

        $cart_item_resource = new CartItemResource($cartItem);

        return response()->json([
            'cart_item' => $cart_item_resource,
        ]);
    }

    public function storeCart(CartRequest $request)
    {
        $userId = Auth::id();

        $cart = Cart::where('user_id', $userId)->first();

        if ($cart) {
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
        $product = Product::find($data['product_id']);

        if (! $product) {
            return response()->json([
                'message' => 'Product not found.',
            ], 404);
        }

        $isSnackCategory = $product->category === 'snack';

        if ($isSnackCategory) {
            $data['temperatur'] = null;
            $data['size'] = null;
            $data['sugar'] = null;
            $data['ice'] = null;
        } else {
            if ($data['temperatur'] === 'hot') {
                $data['ice'] = null;
            }
        }

        $existingCartItem = CartItem::where('cart_id', $cartId)
            ->where('product_id', $data['product_id'])
            ->where(function ($query) use ($data, $isSnackCategory) {
                $query->where('temperatur', $data['temperatur'])
                    ->where('size', $data['size'])
                    ->where('sugar', $data['sugar'])
                    ->where(function ($subQuery) use ($data, $isSnackCategory) {
                        if ($isSnackCategory) {
                            $subQuery->whereNull('ice');
                        } else {
                            $subQuery->where('ice', $data['ice']);
                        }
                    });
            })
            ->first();

        if ($existingCartItem) {
            $existingCartItem->quantity += $data['quantity'];
            $existingCartItem->save();

            return response()->json([
                'message' => 'Cart item updated successfully.',
                'cart' => new CartItemResource($existingCartItem),
            ], 200);
        } else {
            $cartItem = new CartItem();
            $cartItem->cart_id = $cartId;
            $cartItem->fill($data);
            $cartItem->note = $data['note'] ?? null;
            $cartItem->save();

            return response()->json([
                'message' => 'Cart item added successfully.',
                'cart' => new CartItemResource($cartItem),
            ], 201);
        }
    }

    public function updateCartItem($id, CartItemRequest $request)
    {
        $data = $request->validated();

        $cartItem = CartItem::find($id);

        if (! $cartItem) {
            return response()->json(
                [
                    'message' => 'Cart item not found.',
                ],
                404
            );
        }

        $userId = Auth::id();
        $cart = Cart::where('id', $cartItem->cart_id)->where('user_id', $userId)->first();
        if (! $cart) {
            return response()->json(
                [
                    'message' => 'Unauthorized action.',
                ],
                403
            );
        }

        $product = Product::find($cartItem->product_id);
        if (! $product) {
            return response()->json(
                [
                    'message' => 'Product not found.',
                ],
                404
            );
        }

        $isSnackCategory = $product->category === 'snack';

        if ($isSnackCategory) {
            $cartItem->temperatur = null;
            $cartItem->size = null;
            $cartItem->sugar = null;
            $cartItem->ice = null;
        } else {
            if ($data['temperatur'] === 'hot') {
                $cartItem->ice = null;
            } else {
                $cartItem->ice = $data['ice'];
            }
            $cartItem->temperatur = $data['temperatur'];
            $cartItem->size = $data['size'];
            $cartItem->sugar = $data['sugar'];
        }

        $cartItem->quantity = $data['quantity'];
        $cartItem->note = $data['note'] ?? $cartItem->note;

        $cartItem->save();

        return response()->json(
            [
                'message' => 'Cart item updated successfully.',
                'cart' => new CartItemResource($cartItem),
            ],
            200
        );
    }

    public function applyVoucher(ApplyVoucherRequest $request)
    {
        $userId = Auth::id();
        $data = $request->validated();

        $cart = Cart::where('user_id', $userId)->first();

        if (! $cart) {
            return response()->json([
                'message' => 'Cart not found for this user.',
            ], 404);
        }

        $voucher = Voucher::find($data['voucher_id']);

        if (! $voucher) {
            return response()->json([
                'message' => 'Voucher not found.',
            ], 404);
        }

        $userVoucher = UserVoucher::where('user_id', $userId)
            ->where('voucher_id', $voucher->id)
            ->first();

        if ($userVoucher && $userVoucher->used) {
            return response()->json([
                'message' => 'Voucher has already been used.',
            ], 400);
        }

        $cart->voucher_id = $voucher->id;
        $cart->save();

        return response()->json([
            'message' => 'Voucher applied successfully.',
        ], 200);
    }

    public function removeVoucher(Request $request)
    {
        $userId = Auth::id();

        $cart = Cart::where('user_id', $userId)->first();

        if (! $cart) {
            return response()->json([
                'message' => 'Cart not found for this user.',
            ], 404);
        }

        $cart->voucher_id = null;
        $cart->save();

        return response()->json([
            'message' => 'Voucher removed successfully.',
        ], 200);
    }

    public function updateCartItemQuantity($cartItemId, Request $request)
    {
        $cartItem = CartItem::findOrFail($cartItemId);

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
            'message' => 'Cart item quantity updated successfully.',
            'cart_item' => new CartItemResource($cartItem),
        ]);
    }

    public function deleteCartItem($cartItemId)
    {
        $userId = Auth::id();

        $cart = Cart::where('user_id', $userId)->first();

        if (! $cart) {
            return response()->json(
                [
                    'message' => 'Cart not found.',
                ],
                404
            );
        }

        $cartItem = CartItem::where('cart_id', $cart->id)
            ->where('id', $cartItemId)
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
