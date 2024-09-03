<?php

namespace App\Http\Controllers;

use App\Http\Requests\ApplyVoucherRequest;
use App\Http\Requests\CartItemRequest;
use App\Http\Requests\CartRequest;
use App\Http\Resources\CartItemResource;
use App\Http\Resources\CartResource;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\PointConfiguration;
use App\Models\Product;
use App\Models\SessionTime;
use App\Models\Voucher;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CartController extends Controller
{
    protected $statusStoreService;

    public function __construct(StatusStoreController $statusStoreService)
    {
        $this->statusStoreService = $statusStoreService;
    }

    public function showCartByUser(): JsonResponse
    {
        $user_id = Auth::id();
        $user = Auth::user();

        $cart = Cart::where('user_id', $user_id)->first();

        $isPhone = ! empty($user->whatsapp_number);

        $session1 = SessionTime::find(1);
        $session2 = SessionTime::find(2);

        $session1Time = $session1 ? Carbon::parse($session1->start_time)->format('H:i').'-'.Carbon::parse($session1->end_time)->format('H:i') : null;
        $session2Time = $session2 ? Carbon::parse($session2->start_time)->format('H:i').'-'.Carbon::parse($session2->end_time)->format('H:i') : null;

        $endOrderSession1 = $session1 ? Carbon::parse($session1->end_time)->subMinutes(20)->format('H:i') : null;
        $endOrderSession2 = $session2 ? Carbon::parse($session2->end_time)->subMinutes(20)->format('H:i') : null;

        $schedulePickup = $this->statusStoreService->storeStatus()->getData()->data->time ?? 'Toko Sedang Tutup';

        if (! $cart) {
            return response()->json([
                'cart' => [
                    'id' => null,
                    'user_id' => $user_id,
                    'voucher_id' => null,
                    'total_price' => 0,
                    'discount_amount' => 0,
                    'original_price' => 0,
                    'reward_point' => 0,
                    'schedule_pickup' => $schedulePickup,
                    'session_1' => $session1Time,
                    'session_2' => $session2Time,
                    'endOrderSession_1' => $endOrderSession1,
                    'endOrderSession_2' => $endOrderSession2,
                    'is_phone' => $isPhone,
                    'cart_items' => [],
                ],
            ]);
        }

        $cart_items = CartItem::with('product')->where('cart_id', $cart->id)->get();

        $total_price = $cart_items->sum(function ($cart_item) {
            return $cart_item->quantity * $cart_item->price;
        });

        $discount_amount = 0;
        if ($cart->voucher_id) {
            $voucher = Voucher::find($cart->voucher_id);
            if ($voucher) {
                if ($total_price >= $voucher->min_transaction) {
                    $discount_percentage = $voucher->discount;
                    $discount_amount = ($discount_percentage / 100) * $total_price;

                    if (isset($voucher->max_discount) && $discount_amount > $voucher->max_discount) {
                        $discount_amount = $voucher->max_discount;
                    }
                } else {
                    $voucher->is_used = false;
                    $voucher->save();

                    $cart->voucher_id = null;
                    $cart->save();
                }
            }
        }

        $total_price_after_discount = $total_price - $discount_amount;
        $original_price = $total_price;

        $cart_items_array = $cart_items->map(function ($cart_item) {
            return new CartItemResource($cart_item);
        });

        $totalPrice = $this->cartItems->sum(function ($cartItem) {
            return $cartItem->quantity * $cartItem->price;
        });

        $pointConfig = PointConfiguration::all()->first();
        $minimumAmount = $pointConfig->minimum_amount;
        $collectPoint = $pointConfig->collect_point;

        if ($totalPrice >= $minimumAmount) {
            $rewardPoint = floor($totalPrice / $minimumAmount);
            $rewardPoint += ($totalPrice % $minimumAmount == 0) ? 0 : $collectPoint;
        } else {
            $rewardPoint = 0;
        }

        $cart->cartItems = $cart_items_array;
        $cart->total_price = max(0, $total_price_after_discount);
        $cart->discount_amount = $discount_amount;
        $cart->original_price = $original_price;
        $cart->schedule_pickup = $schedulePickup;
        $cart->reward_point = $rewardPoint;
        $cart->is_phone = $isPhone;
        $cart->session_1 = $session1Time;
        $cart->session_2 = $session2Time;
        $cart->endSession_1 = $endOrderSession1;
        $cart->endSession_2 = $endOrderSession2;

        return response()->json([
            'cart' => (new CartResource($cart)),
        ]);
    }

    public function showCartItemById($cartItemId): JsonResponse
    {
        $user_id = Auth::id();

        $cartItem = CartItem::with('product')
            ->whereHas('cart', function ($query) use ($user_id) {
                $query->where('user_id', $user_id);
            })
            ->where('id', $cartItemId)
            ->first();

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

    public function storeCart(CartRequest $request): JsonResponse
    {
        $userId = Auth::id();

        $cart = Cart::all()->where('user_id', $userId)->first();

        $schedulePickup = $this->statusStoreService->storeStatus()->getData()->data->time ?? 'Toko Sedang Tutup';

        if ($cart) {
            return $this->addCartItem($cart->id, $request);
        } else {
            $cart = new Cart();
            $cart['user_id'] = $userId;
            $cart['schedule_pickup'] = $schedulePickup;
            $cart->save();

            return $this->addCartItem($cart->id, $request);
        }
    }

    public function addCartItem($cartId, CartRequest $request): JsonResponse
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
            $data['price'] = $product->regular_price;
        } else {
            if ($data['temperatur'] === 'hot') {
                $data['ice'] = null;
            }

            if ($data['size'] === 'large') {
                $data['price'] = $product->large_price;
            } else {
                $data['price'] = $product->regular_price;
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

    public function updateCartItem($id, CartItemRequest $request): JsonResponse
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
            if (isset($data['temperatur']) && $data['temperatur'] === 'hot') {
                $cartItem->ice = null;
            } else {
                $cartItem->ice = $data['ice'] ?? $cartItem->ice;
            }
            $cartItem->temperatur = $data['temperatur'] ?? $cartItem->temperatur;
            $cartItem->size = $data['size'] ?? $cartItem->size;
            $cartItem->sugar = $data['sugar'] ?? $cartItem->sugar;

            if (isset($data['size']) && $data['size'] === 'large') {
                $data['price'] = $product->large_price;
            } else {
                $data['price'] = $product->regular_price;
            }
        }

        $cartItem->quantity = $data['quantity'];
        $cartItem->note = $data['note'] ?? $cartItem->note;
        $cartItem->price = $data['price'] ?? $cartItem->price;

        $cartItem->save();

        return response()->json(
            [
                'message' => 'Cart item updated successfully.',
                'cart' => new CartItemResource($cartItem),
            ],
            200
        );
    }

    public function applyVoucher(ApplyVoucherRequest $request): JsonResponse
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

        // Cek jika voucher aktif
        $currentDate = now();
        $activeVouchers = Voucher::where('start_date', '<=', $currentDate)
            ->where('end_date', '>=', $currentDate)
            ->whereDoesntHave('userVouchers', function ($query) use ($userId) {
                $query->where('user_id', $userId)
                    ->where('used', true);
            })
            ->get();

        $isVoucherActive = $activeVouchers->contains('id', $voucher->id);

        if (! $isVoucherActive) {
            return response()->json([
                'message' => 'Voucher is not active or not eligible for use.',
            ], 400);
        }

        $cart_items = CartItem::where('cart_id', $cart->id)->get();
        $total_price = $cart_items->sum(function ($cart_item) {
            return $cart_item->quantity * $cart_item->price;
        });

        if ($total_price < $voucher->min_transaction) {
            return response()->json([
                'message' => 'The total price does not meet the minimum transaction amount required for this voucher.',
            ], 400);
        }

        $voucher->is_used = true;
        $voucher->save();

        $cart->voucher_id = $voucher->id;
        $cart->save();

        return response()->json([
            'message' => 'Voucher applied successfully.',
        ], 200);
    }

    public function removeVoucher(ApplyVoucherRequest $request): JsonResponse
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

        $voucher->is_used = false;
        $voucher->save();

        $cart->voucher_id = null;
        $cart->save();

        return response()->json([
            'message' => 'Voucher removed successfully.',
        ], 200);
    }

    public function updateCartItemQuantity($cartItemId, Request $request): JsonResponse
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

    public function deleteCartItem($cartItemId): JsonResponse
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
