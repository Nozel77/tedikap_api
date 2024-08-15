<?php

namespace App\Http\Controllers;

use App\Http\Requests\OrderRequest;
use App\Http\Resources\CartRewardResource;
use App\Http\Resources\OrderRewardResource;
use App\Models\CartReward;
use App\Models\CartRewardItem;
use App\Models\OrderReward;
use App\Models\OrderRewardItem;
use App\Models\Point;
use App\Models\RewardProduct;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Laravel\Firebase\Facades\Firebase;

class OrderRewardController extends Controller
{
    public function notification(Request $request, $id)
    {
        $FcmToken = User::find($id)->fcm_token;
        $order = OrderReward::find($request->order_reward_id);

        if (! $order) {
            return response()->json(['message' => 'OrderReward not found.'], 404);
        }

        $message = CloudMessage::fromArray([
            'token' => $FcmToken,
            'notification' => [
                'title' => $request->title,
                'body' => $request->body,
            ],
        ])->withData([
            'route' => $request->route,
            'order_id' => $order->id,
        ]);

        Firebase::messaging()->send($message);

        return $message;
    }

    protected function notifyAdmins(Request $request)
    {
        $adminIds = User::where('role', 'admin')->pluck('id');
        foreach ($adminIds as $adminId) {
            $this->notification($request, $adminId);
        }
    }

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

        $ongoingStatuses = ['menunggu konfirmasi', 'pesanan diproses', 'pesanan siap diambil'];
        $historyStatuses = ['pesanan selesai', 'pesanan dibatalkan', 'pesanan ditolak'];

        $query = OrderReward::where('user_id', $user->id)->orderBy('created_at', 'desc');

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

            return $order;
        });

        return response()->json([
            'message' => 'Orders retrieved successfully.',
            'orders' => OrderRewardResource::collection($orders),
        ]);
    }

    public function store(OrderRequest $request)
    {
        $userId = Auth::id();
        $data = $request->validated();

        $rewardCart = CartReward::where('user_id', $userId)
            ->with('rewardCartItems')
            ->first();

        if (! $rewardCart) {
            return response()->json([
                'message' => 'Reward cart not found for the authenticated user.',
            ], 404);
        }

        $totalPoints = $rewardCart->rewardCartItems->sum(function ($rewardCartItem) {
            return $rewardCartItem->quantity * $rewardCartItem->points;
        });

        $userPoints = Point::where('user_id', $userId)->sum('point');
        if ($userPoints < $totalPoints) {
            return response()->json([
                'message' => 'Points not enough.',
            ], 400);
        }

        $remainingPoints = $userPoints - $totalPoints;
        Point::where('user_id', $userId)->update(['point' => $remainingPoints]);

        $order = new OrderReward();
        $order->id = $this->generateCustomUUID();
        $order->user_id = $userId;
        $order->cart_reward_id = $rewardCart->id;
        $order->total_point = $totalPoints;
        $order->status = 'menunggu konfirmasi';
        $order->status_description = 'mohon segera lakukan pembayaran supaya pesanan Anda dapat diproses';
        $whatsappMessage = urlencode("halo saya ingin tanya tentang pesanan saya dengan id {$order->id}");
        $order->whatsapp = "https://wa.me/62895395343223?text={$whatsappMessage}";
        $order->expires_at = now()->addMinutes(5);
        $order->icon_status = 'ic_status_waiting';
        $order->order_type = 'reward order';
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

        $userNotification = new Request([
            'title' => 'Pemesanan Berhasil',
            'body' => 'Pesanan Anda sekarang sedang menunggu konfirmasi dari admin. Kami akan segera memprosesnya dan memberi tahu Anda jika ada pembaruan lebih lanjut.',
            'route' => 'detail_order_reward',
            'order_id' => $order->id,
        ]);
        $notif = $this->notification($userNotification, $userId);

        $adminNotification = new Request([
            'title' => 'Pesanan Baru - Menunggu Konfirmasi',
            'body' => "Pesanan baru dengan ID: {$order->id} telah dibuat dan menunggu konfirmasi. Silakan periksa pesanan baru di sistem admin.",
            'route' => '',
        ]);
        $this->notifyAdmins($adminNotification);

        foreach ($rewardCart->rewardCartItems as $rewardCartItem) {
            $rewardProduct = RewardProduct::find($rewardCartItem->reward_product_id);

            if ($rewardProduct) {
                if ($rewardProduct->category === 'snack') {
                    $temperatur = null;
                    $size = null;
                    $sugar = null;
                    $ice = null;
                } else {
                    $temperatur = $rewardCartItem->temperatur;
                    $size = $rewardCartItem->size;
                    $sugar = $rewardCartItem->sugar;
                    $ice = ($rewardCartItem->temperatur === 'hot') ? null : $rewardCartItem->ice;
                }

                $orderItem = new OrderRewardItem();
                $orderItem->order_reward_id = $order->id;
                $orderItem->reward_product_id = $rewardCartItem->reward_product_id;
                $orderItem->item_type = 'reward';
                $orderItem->quantity = $rewardCartItem->quantity;
                $orderItem->points = $rewardCartItem->points;
                $orderItem->temperatur = $temperatur;
                $orderItem->size = $size;
                $orderItem->sugar = $sugar;
                $orderItem->ice = $ice;
                $orderItem->save();
            }
        }

        $rewardCart->rewardCartItems()->delete();

        return response()->json([
            'message' => 'Reward order placed successfully.',
            'order' => new OrderRewardResource($order),
            'notification' => $notif,
        ], 201);
    }

    public function show($id)
    {
        $user = Auth::user();

        $order = OrderReward::where('id', $id)->where('user_id', $user->id)->first();

        if (! $order) {
            return response()->json([
                'message' => 'Order Reward not found.',
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
            'order' => new OrderRewardResource($order),
        ]);
    }

    public function reorderReward($orderId)
    {
        $userId = Auth::id();

        $previousOrder = OrderReward::with('orderRewardItems')
            ->where('id', $orderId)
            ->where('user_id', $userId)
            ->first();

        if (! $previousOrder) {
            return response()->json([
                'message' => 'Order not found or not authorized.',
            ], 404);
        }

        $rewardCart = CartReward::firstOrCreate(['user_id' => $userId]);

        $rewardCart->rewardCartItems()->delete();

        foreach ($previousOrder->orderRewardItems as $orderRewardItem) {
            $cartItem = new CartRewardItem();
            $cartItem->cart_reward_id = $rewardCart->id;
            $cartItem->reward_product_id = $orderRewardItem->reward_product_id;
            $cartItem->quantity = $orderRewardItem->quantity;
            $cartItem->points = $orderRewardItem->points;
            $cartItem->temperatur = $orderRewardItem->temperatur;
            $cartItem->size = $orderRewardItem->size;
            $cartItem->sugar = $orderRewardItem->sugar;
            $cartItem->ice = $orderRewardItem->ice;
            $cartItem->save();
        }

        return response()->json([
            'message' => 'Reward items successfully added to reward cart.',
            'cart' => new CartRewardResource($rewardCart),
        ], 200);
    }
}
