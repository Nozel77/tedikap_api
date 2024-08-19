<?php

namespace App\Http\Controllers;

use App\Http\Resources\OrderResource;
use App\Http\Resources\OrderRewardResource;
use App\Models\Order;
use App\Models\OrderReward;
use Illuminate\Http\Request;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Laravel\Firebase\Facades\Firebase;

class AdminController extends Controller
{
    public function notification($fcmToken, $title, $body, $route, $orderId)
    {
        $message = CloudMessage::fromArray([
            'token' => $fcmToken,
            'notification' => [
                'title' => $title,
                'body' => $body,
            ],
        ])->withData([
            'route' => $route,
            'order_id' => $orderId,
        ]);

        return Firebase::messaging()->send($message);
    }

    public function getOrderAdmin()
    {
        $filterStatus = request()->query('status');
        $filterSesi = request()->query('session');

        $validStatuses = [
            'new order' => 'menunggu konfirmasi',
            'proccess' => 'pesanan diproses',
            'taken' => 'pesanan siap diambil',
            'done' => 'pesanan selesai',
        ];

        $orderQuery = Order::query();
        $orderRewardQuery = OrderReward::query();

        if (array_key_exists($filterStatus, $validStatuses)) {
            $orderQuery->where('status', $validStatuses[$filterStatus]);
            $orderRewardQuery->where('status', $validStatuses[$filterStatus]);
        } else {
            $orderQuery->whereIn('status', array_values($validStatuses));
            $orderRewardQuery->whereIn('status', array_values($validStatuses));
        }

        if ($filterSesi === '1') {
            $orderQuery->where('schedule_pickup', '09:40-10:00');
            $orderRewardQuery->where('schedule_pickup', '09:40-10:00');
        } elseif ($filterSesi === '2') {
            $orderQuery->where('schedule_pickup', '12:00-12:30');
            $orderRewardQuery->where('schedule_pickup', '12:00-12:30');
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
        $user = null;
        $notificationData = [];

        if ($order) {
            if ($action == 'accepted') {
                $order->status = 'pesanan diproses';
                $order->status_description = 'Pesanan Anda sedang diproses';
                $order->icon_status = 'ic_status_waiting';

                $notificationData = [
                    'title' => 'Pesanan Anda Sedang Diproses',
                    'body' => 'Terima kasih telah melakukan pemesanan! Pesanan Anda saat ini sedang diproses dan akan segera dikirimkan. Kami akan mengupdate status pesanan Anda jika ada perubahan.',
                    'route' => '',
                ];
            } elseif ($action == 'rejected') {
                $order->status = 'pesanan ditolak';
                $order->status_description = 'Pesanan Anda ditolak';
                $order->icon_status = 'ic_status_canceled';

                $notificationData = [
                    'title' => $request->input('title', 'Pesanan Anda Ditolak'),
                    'body' => $request->input('body', 'Maaf, pesanan Anda telah ditolak. Jika Anda merasa ada kesalahan, silakan hubungi kami.'),
                    'route' => '',
                ];
            }
            $order->save();
            $user = $order->user;
        } else {
            $orderReward = OrderReward::where('id', $id)->where('status', 'menunggu konfirmasi')->first();

            if ($orderReward) {
                if ($action == 'accepted') {
                    $orderReward->status = 'pesanan diproses';
                    $orderReward->status_description = 'Pesanan Anda sedang diproses';
                    $orderReward->icon_status = 'ic_status_waiting';

                    $notificationData = [
                        'title' => 'Pesanan Anda Sedang Diproses',
                        'body' => 'Terima kasih telah melakukan pemesanan! Pesanan Anda saat ini sedang diproses dan akan segera dikirimkan. Kami akan mengupdate status pesanan Anda jika ada perubahan.',
                        'route' => '',
                    ];
                } elseif ($action == 'rejected') {
                    $orderReward->status = 'pesanan ditolak';
                    $orderReward->status_description = 'Pesanan Anda ditolak';
                    $orderReward->icon_status = 'ic_status_canceled';

                    $notificationData = [
                        'title' => $request->input('title', 'Pesanan Anda Ditolak'),
                        'body' => $request->input('body', 'Maaf, pesanan hadiah Anda telah ditolak. Jika Anda merasa ada kesalahan, silakan hubungi kami.'),
                        'route' => '',
                    ];
                }
                $orderReward->save();
                $user = $orderReward->user;
            }
        }

        if ($user && $notificationData) {
            $fcmToken = $user->fcm_token;
            $notif = $this->notification($fcmToken, $notificationData['title'], $notificationData['body'], $notificationData['route'], $id);

            return response()->json([
                'message' => 'Order status updated successfully.',
                'order' => $order ? new OrderResource($order) : new OrderRewardResource($orderReward),
                'notification' => $notif,
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
        $user = null;

        if ($order) {
            if ($action == 'accepted') {
                $order->status = 'pesanan siap diambil';
                $order->status_description = 'Pesanan Anda saat ini siap untuk diambil';
                $order->icon_status = 'ic_status_ready';

                $title = 'Pesanan Anda Siap Diambil';
                $body = 'Pesanan Anda saat ini siap untuk diambil. Silakan ambil pesanan Anda sesuai dengan informasi yang diberikan.';
            } elseif ($action == 'rejected') {
                $order->status = 'pesanan dibatalkan';
                $order->status_description = 'Pesanan Anda dibatalkan';
                $order->icon_status = 'ic_status_canceled';

                $title = 'Pesanan Anda Dibatalkan';
                $body = 'Maaf, pesanan Anda telah dibatalkan. Jika Anda merasa ada kesalahan, silakan hubungi kami.';
            }
            $order->save();
            $user = $order->user;

            $notif = $this->notification($user->fcm_token, $title, $body, '', $order->id);

            return response()->json([
                'message' => 'Order status updated successfully.',
                'order' => new OrderResource($order),
                'notification' => $notif,
            ], 200);
        }

        $orderReward = OrderReward::where('id', $id)->where('status', 'pesanan diproses')->first();

        if ($orderReward) {
            if ($action == 'accepted') {
                $orderReward->status = 'pesanan siap diambil';
                $orderReward->status_description = 'Pesanan adalah saat ini siap untuk diambil';
                $orderReward->icon_status = 'ic_status_ready';

                $title = 'Pesanan Hadiah Anda Siap Diambil';
                $body = 'Pesanan hadiah Anda saat ini siap untuk diambil. Silakan ambil pesanan hadiah Anda sesuai dengan informasi yang diberikan.';
            } elseif ($action == 'rejected') {
                $orderReward->status = 'pesanan dibatalkan';
                $orderReward->status_description = 'Pesanan hadiah Anda dibatalkan';
                $orderReward->icon_status = 'ic_status_canceled';

                $title = 'Pesanan Hadiah Anda Dibatalkan';
                $body = 'Maaf, pesanan hadiah Anda telah dibatalkan. Jika Anda merasa ada kesalahan, silakan hubungi kami.';
            }
            $orderReward->save();
            $user = $orderReward->user;

            $notif = $this->notification($user->fcm_token, $title, $body, '', $orderReward->id);

            return response()->json([
                'message' => 'Order status updated successfully.',
                'order' => new OrderRewardResource($orderReward),
                'notification' => $notif,
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
        $user = null;

        if ($order) {
            if ($action == 'accepted') {
                $order->status = 'pesanan selesai';
                $order->status_description = 'Pesanan Anda telah selesai';
                $order->icon_status = 'ic_status_done';

                $title = 'Pesanan Anda Selesai';
                $body = 'Pesanan Anda telah selesai. Terima kasih telah berbelanja dengan kami. Kami berharap Anda puas dengan layanan kami.';
            } elseif ($action == 'rejected') {
                $order->status = 'pesanan dibatalkan';
                $order->status_description = 'Pesanan Anda dibatalkan';
                $order->icon_status = 'ic_status_canceled';

                $title = 'Pesanan Anda Dibatalkan';
                $body = 'Maaf, pesanan Anda telah dibatalkan. Jika Anda merasa ada kesalahan atau ingin melakukan pemesanan ulang, silakan hubungi kami.';
            }
            $order->save();
            $user = $order->user;

            $notif = $this->notification($user->fcm_token, $title, $body, '', $order->id);

            return response()->json([
                'message' => 'Order status updated successfully.',
                'order' => new OrderResource($order),
                'notification' => $notif,
            ], 200);
        }

        $orderReward = OrderReward::where('id', $id)->where('status', 'pesanan siap diambil')->first();

        if ($orderReward) {
            if ($action == 'accepted') {
                $orderReward->status = 'pesanan selesai';
                $orderReward->status_description = 'Pesanan hadiah Anda telah selesai';
                $orderReward->icon_status = 'ic_status_done';

                $title = 'Pesanan Hadiah Anda Selesai';
                $body = 'Pesanan hadiah Anda telah selesai. Terima kasih telah berbelanja dengan kami. Kami berharap Anda puas dengan layanan kami.';
            } elseif ($action == 'rejected') {
                $orderReward->status = 'pesanan dibatalkan';
                $orderReward->status_description = 'Pesanan hadiah Anda dibatalkan';
                $orderReward->icon_status = 'ic_status_canceled';

                $title = 'Pesanan Hadiah Anda Dibatalkan';
                $body = 'Maaf, pesanan hadiah Anda telah dibatalkan. Jika Anda merasa ada kesalahan atau ingin melakukan pemesanan ulang, silakan hubungi kami.';
            }
            $orderReward->save();
            $user = $orderReward->user;

            $notif = $this->notification($user->fcm_token, $title, $body, '', $orderReward->id);

            return response()->json([
                'message' => 'Order status updated successfully.',
                'order' => new OrderRewardResource($orderReward),
                'notification' => $notif,
            ], 200);
        }

        return response()->json([
            'message' => 'Order not found or not in the "pesanan siap diambil" status.',
        ], 404);
    }
}
