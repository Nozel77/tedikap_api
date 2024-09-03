<?php

namespace App\Http\Controllers;

use App\Http\Requests\CartRewardItemRequest;
use App\Http\Requests\CartRewardRequest;
use App\Http\Resources\CartRewardItemResource;
use App\Http\Resources\CartRewardResource;
use App\Models\CartReward;
use App\Models\CartRewardItem;
use App\Models\Point;
use App\Models\RewardProduct;
use App\Models\SessionTime;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CartRewardController extends Controller
{
    protected $statusStoreService;

    public function __construct(StatusStoreController $statusStoreService)
    {
        $this->statusStoreService = $statusStoreService;
    }
    public function showCartByUser()
    {
        $user_id = Auth::id();
        $user = Auth::user();

        $cart = CartReward::where('user_id', $user_id)->first();

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
                    'total_points' => 0,
                    'schedule_pickup' => $schedulePickup,
                    'session_1' => $session1Time,
                    'session_2' => $session2Time,
                    'endOrderSession_1' => $endOrderSession1,
                    'endOrderSession_2' => $endOrderSession2,
                    'points_enough' => false,
                    'is_phone' => $isPhone,
                    'cart_items' => [],
                ],
            ], 200);
        }

        $cart_items = CartRewardItem::where('cart_reward_id', $cart->id)->get();
        $total_points = $cart_items->sum(function ($cart_item) {
            return $cart_item->quantity * $cart_item->points;
        });

        $requiredPoints = $total_points;
        $userPoints = Point::where('user_id', $user_id)->sum('point');
        $pointsEnough = $userPoints >= $requiredPoints;

        $cart_items_array = $cart_items->map(function ($cart_item) {
            return new CartRewardItemResource($cart_item);
        });

        $cart->cartItems = $cart_items_array;
        $cart->schedule_pickup = $schedulePickup;
        $cart->total_points = $total_points;
        $cart->points_enough = $pointsEnough;
        $cart->is_phone = $isPhone;
        $cart->session_1 = $session1Time;
        $cart->session_2 = $session2Time;
        $cart->endSession_1 = $endOrderSession1;
        $cart->endSession_2 = $endOrderSession2;

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

        $schedulePickup = $this->statusStoreService->storeStatus()->getData()->data->time ?? 'Toko Sedang Tutup';

        if ($cart) {
            return $this->addCartItem($cart->id, $request);
        } else {
            $cart = new CartReward();
            $cart->user_id = $userId;
            $cart->schedule_pickup = $schedulePickup;
            $cart->save();

            return $this->addCartItem($cart->id, $request);
        }
    }

    public function addCartItem($cartId, CartRewardRequest $request)
    {
        $data = $request->validated();

        $rewardProduct = RewardProduct::find($data['reward_product_id']);
        if (! $rewardProduct) {
            return response()->json([
                'message' => 'Reward product not found.',
            ], 404);
        }

        $isMerchandiseCategory = $rewardProduct->category === 'merchandise';

        if ($isMerchandiseCategory) {
            $data['temperatur'] = null;
            $data['size'] = null;
            $data['sugar'] = null;
            $data['ice'] = null;
            $data['points'] = $rewardProduct->regular_point;
        } else {
            if ($data['temperatur'] === 'hot') {
                $data['ice'] = null;
            }

            if ($data['size'] === 'large') {
                $data['points'] = $rewardProduct->large_point;
            } else {
                $data['points'] = $rewardProduct->regular_point;
            }
        }

        $existingCartRewardItem = CartRewardItem::where('cart_reward_id', $cartId)
            ->where('reward_product_id', $data['reward_product_id'])
            ->where(function ($query) use ($data, $isMerchandiseCategory) {
                $query->where('temperatur', $data['temperatur'])
                    ->where('size', $data['size'])
                    ->where('sugar', $data['sugar'])
                    ->where(function ($subQuery) use ($data, $isMerchandiseCategory) {
                        if ($isMerchandiseCategory) {
                            $subQuery->whereNull('ice');
                        } else {
                            $subQuery->where('ice', $data['ice']);
                        }
                    });
            })
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

        $cartItem = CartRewardItem::whereHas('cartReward', function ($query) use ($user_id) {
            $query->where('user_id', $user_id);
        })->where('id', $cartItemId)->first();

        if (! $cartItem) {
            return response()->json([
                'message' => 'Cart item not found for this user.',
            ], 404);
        }

        $data = $request->validated();

        $rewardProduct = RewardProduct::find($cartItem->reward_product_id);
        if (! $rewardProduct) {
            return response()->json([
                'message' => 'Reward product not found.',
            ], 404);
        }

        $isSnackCategory = $rewardProduct->category === 'snack';

        if ($isSnackCategory) {
            $cartItem->temperatur = null;
            $cartItem->size = null;
            $cartItem->sugar = null;
            $cartItem->ice = null;
            $cartItem->points = $rewardProduct->regular_point;
        } else {
            if (isset($data['temperatur']) && $data['temperatur'] === 'hot') {
                $cartItem->ice = null;
            } else {
                $cartItem->ice = $data['ice'] ?? $cartItem->ice;
            }
            $cartItem->temperatur = $data['temperatur'] ?? $cartItem->temperatur;
            $cartItem->size = $data['size'] ?? $cartItem->size;
            $cartItem->sugar = $data['sugar'] ?? $cartItem->sugar;

            if ($cartItem->size === 'large') {
                $cartItem->points = $rewardProduct->large_point;
            } else {
                $cartItem->points = $rewardProduct->regular_point;
            }
        }

        $cartItem->quantity = $data['quantity'] ?? $cartItem->quantity;
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

        $cartItem = CartRewardItem::whereHas('cartReward', function ($query) use ($userId) {
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
