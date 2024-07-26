<?php

namespace App\Http\Controllers;

use App\Http\Requests\OrderRequest;
use App\Http\Resources\OrderResource;
use App\Http\Resources\OrderRewardResource;
use App\Models\Cart;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderReward;
use App\Models\Product;
use App\Models\UserVoucher;
use App\Models\Voucher;
use Illuminate\Http\Request;
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

    public function index(Request $request)
    {
        $user = Auth::user();

        $filterType = $request->query('type');

        $ongoingStatuses = ['menunggu pembayaran', 'menunggu konfirmasi', 'pesanan diproses', 'pesanan siap diambil'];
        $historyStatuses = ['pesanan selesai', 'pesanan dibatalkan', 'pesanan ditolak'];

        $query = Order::where('user_id', $user->id)->with('payment')->orderBy('created_at', 'desc');

        if ($filterType === 'ongoing') {
            $query->whereIn('status', $ongoingStatuses);
        } elseif ($filterType === 'history') {
            $query->whereIn('status', $historyStatuses);
        }

        $orders = $query->get();

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

            if ($order->payment) {
                $order->payment_channel = $order->payment->payment_channel;
            } else {
                $order->payment_channel = null;
            }

            return $order;
        });

        return response()->json([
            'message' => 'Orders retrieved successfully.',
            'orders' => OrderResource::collection($orders),
        ]);
    }

    public function storeRegularOrder(OrderRequest $request)
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
        $order->status = 'menunggu pembayaran';
        $order->order_type = 'order';
        $order->save();

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
        $order->save();

        foreach ($cart->cartItems as $cartItem) {
            $product = Product::find($cartItem->product_id);
            if ($product && $product->category === 'snack') {
                $cartItem->temperatur = null;
                $cartItem->size = null;
                $cartItem->sugar = null;
                $cartItem->ice = null;
            } elseif ($cartItem->temperatur === 'hot') {
                $cartItem->ice = null;
            }

            $orderItem = new OrderItem();
            $orderItem->order_id = $order->id;
            $orderItem->product_id = $cartItem->product_id;
            $orderItem->item_type = 'product';
            $orderItem->temperatur = $cartItem->temperatur;
            $orderItem->ice = $cartItem->ice;
            $orderItem->size = $cartItem->size;
            $orderItem->sugar = $cartItem->sugar;
            $orderItem->note = $cartItem->note;
            $orderItem->quantity = $cartItem->quantity;
            $orderItem->price = $cartItem->price;
            $orderItem->save();
        }

        $cart->cartItems()->delete();

        $cart->voucher_id = null;
        $cart->save();

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

    public function getOrderAdmin()
    {
        $filterStatus = request()->query('status');
        $validStatuses = ['new order' => 'menunggu konfirmasi', 'proccess' => 'pesanan diproses', 'taken' => 'pesanan siap diambil'];

        $orderQuery = Order::query();
        $orderRewardQuery = OrderReward::query();

        if (array_key_exists($filterStatus, $validStatuses)) {
            $orderQuery->where('status', $validStatuses[$filterStatus]);
            $orderRewardQuery->where('status', $validStatuses[$filterStatus]);
        } else {
            $orderQuery->whereIn('status', array_values($validStatuses));
            $orderRewardQuery->whereIn('status', array_values($validStatuses));
        }

        $orders = $orderQuery->orderBy('created_at', 'desc')->get();
        $orderRewards = $orderRewardQuery->orderBy('created_at', 'desc')->get();

        $combinedOrders = $orders->map(function ($order) {
            if ($order->order_type === 'reward order') {
                return (new OrderRewardResource($order))->toArray(request());
            } else {
                return (new OrderResource($order))->toArray(request());
            }
        });

        $combinedOrderRewards = $orderRewards->map(function ($orderReward) {
            return (new OrderRewardResource($orderReward))->toArray(request());
        });

        $allOrders = $combinedOrders->concat($combinedOrderRewards)->sortByDesc('created_at')->values()->all();

        return response()->json([
            'message' => 'Orders retrieved successfully.',
            'orders' => $allOrders,
        ]);
    }

    public function updateStatusOrder(Request $request, $id)
    {
        $action = $request->query('action');

        if (! $action || ! in_array($action, ['accepted', 'rejected'])) {
            return response()->json([
                'message' => 'Invalid action.',
            ], 400);
        }

        $order = Order::where('id', $id)->where('status', 'menunggu konfirmasi')->first();

        if ($order) {
            if ($action == 'accepted') {
                $order->status = 'pesanan diproses';
            } elseif ($action == 'rejected') {
                $order->status = 'pesanan ditolak';
            }
            $order->save();

            return response()->json([
                'message' => 'Order status updated successfully.',
                'order' => new OrderResource($order),
            ], 200);
        }

        $orderReward = OrderReward::where('id', $id)->where('status', 'menunggu konfirmasi')->first();

        if ($orderReward) {
            if ($action == 'accepted') {
                $orderReward->status = 'pesanan diproses';
            } elseif ($action == 'rejected') {
                $orderReward->status = 'pesanan ditolak';
            }
            $orderReward->save();

            return response()->json([
                'message' => 'Order status updated successfully.',
                'order' => new OrderRewardResource($orderReward),
            ], 200);
        }

        return response()->json([
            'message' => 'Order not found or not in the "menunggu konfirmasi" status.',
        ], 404);
    }

    public function updateStatusOrderSiap(Request $request, $id)
    {
        $action = $request->query('action');

        if (! $action || ! in_array($action, ['accepted', 'rejected'])) {
            return response()->json([
                'message' => 'Invalid action.',
            ], 400);
        }

        $order = Order::where('id', $id)->where('status', 'pesanan diproses')->first();

        if ($order) {
            if ($action == 'accepted') {
                $order->status = 'pesanan siap diambil';
            } elseif ($action == 'rejected') {
                $order->status = 'pesanan dibatalkan';
            }
            $order->save();

            return response()->json([
                'message' => 'Order status updated successfully.',
                'order' => new OrderResource($order),
            ], 200);
        }

        $orderReward = OrderReward::where('id', $id)->where('status', 'pesanan diproses')->first();

        if ($orderReward) {
            if ($action == 'accepted') {
                $orderReward->status = 'pesanan siap diambil';
            } elseif ($action == 'rejected') {
                $orderReward->status = 'pesanan dibatalkan';
            }
            $orderReward->save();

            return response()->json([
                'message' => 'Order status updated successfully.',
                'order' => new OrderRewardResource($orderReward),
            ], 200);
        }

        return response()->json([
            'message' => 'Order not found or not in the "pesanan diproses" status.',
        ], 404);
    }

    public function updateStatusOrderSelesai(Request $request, $id)
    {
        $action = $request->query('action');

        if (! $action || ! in_array($action, ['accepted', 'rejected'])) {
            return response()->json([
                'message' => 'Invalid action.',
            ], 400);
        }

        $order = Order::where('id', $id)->where('status', 'pesanan siap diambil')->first();

        if ($order) {
            if ($action == 'accepted') {
                $order->status = 'pesanan selesai';
            } elseif ($action == 'rejected') {
                $order->status = 'pesanan dibatalkan';
            }
            $order->save();

            return response()->json([
                'message' => 'Order status updated successfully.',
                'order' => new OrderResource($order),
            ], 200);
        }

        $orderReward = OrderReward::where('id', $id)->where('status', 'pesanan siap diambil')->first();

        if ($orderReward) {
            if ($action == 'accepted') {
                $orderReward->status = 'pesanan selesai';
            } elseif ($action == 'rejected') {
                $orderReward->status = 'pesanan dibatalkan';
            }
            $orderReward->save();

            return response()->json([
                'message' => 'Order status updated successfully.',
                'order' => new OrderRewardResource($orderReward),
            ], 200);
        }

        return response()->json([
            'message' => 'Order not found or not in the "pesanan siap diambil" status.',
        ], 404);
    }
}
