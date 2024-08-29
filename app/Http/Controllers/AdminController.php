<?php

namespace App\Http\Controllers;

use App\Http\Resources\OrderResource;
use App\Http\Resources\OrderRewardResource;
use App\Models\Order;
use App\Models\OrderReward;
use App\Models\Point;
use Illuminate\Http\Request;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Laravel\Firebase\Facades\Firebase;

class AdminController extends Controller
{
    public function getOrderAdmin()
    {
        $filterStatus = request()->query('status');
        $filterSesi = request()->query('session');
        $filterType = request()->query('type');

        $validStatuses = [
            'new order' => 'menunggu konfirmasi',
            'proccess' => 'pesanan diproses',
            'taken' => 'pesanan siap diambil',
            'done' => 'pesanan selesai',
        ];

        $orders = collect();

        if ($filterType === 'order' || ! $filterType) {
            $orderQuery = Order::query();

            if (array_key_exists($filterStatus, $validStatuses)) {
                $orderQuery->where('status', $validStatuses[$filterStatus]);
            } else {
                $orderQuery->whereIn('status', array_values($validStatuses));
            }

            if ($filterSesi === '1') {
                $orderQuery->where('schedule_pickup', '09:40-10:00');
            } elseif ($filterSesi === '2') {
                $orderQuery->where('schedule_pickup', '12:00-12:30');
            }

            $orders = $orders->merge($orderQuery->orderBy('created_at', 'desc')->get());
        }

        if ($filterType === 'reward' || ! $filterType) {
            $orderRewardQuery = OrderReward::query();

            if (array_key_exists($filterStatus, $validStatuses)) {
                $orderRewardQuery->where('status', $validStatuses[$filterStatus]);
            } else {
                $orderRewardQuery->whereIn('status', array_values($validStatuses));
            }

            if ($filterSesi === '1') {
                $orderRewardQuery->where('schedule_pickup', '09:40-10:00');
            } elseif ($filterSesi === '2') {
                $orderRewardQuery->where('schedule_pickup', '12:00-12:30');
            }

            $orders = $orders->merge($orderRewardQuery->orderBy('created_at', 'desc')->get());
        }

        $totalOrders = $orders->count();

        $formattedOrders = $orders->map(function ($order) {
            if ($order instanceof Order) {
                return (new OrderResource($order))->toArray(request());
            } elseif ($order instanceof OrderReward) {
                return (new OrderRewardResource($order))->toArray(request());
            }
        });

        return response()->json([
            'message' => 'Order Berhasil Diterima',
            'orders_length' => $totalOrders,
            'orders' => $formattedOrders,
        ], 200);
    }

    public function updateStatusOrder(Request $request, $id)
    {
        $action = $request->query('action');

        if (! $action || ! in_array($action, ['accepted', 'rejected'])) {
            return response()->json([
                'message' => 'Tidak ada nilai action yang valid.',
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
                    'route' => 'detail_order_common',
                ];
            } elseif ($action == 'rejected') {
                $order->status = 'pesanan ditolak';
                $order->status_description = $request->input('body', 'Pesanan Anda ditolak');
                $order->icon_status = 'ic_status_canceled';

                $notificationData = [
                    'title' => 'Pesanan anda ditolak',
                    'body' => $request->input('body', 'Maaf, pesanan Anda telah ditolak. Jika Anda merasa ada kesalahan, silakan hubungi kami.'),
                    'route' => 'detail_order_common',
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
                        'route' => 'detail_order_reward',
                    ];
                } elseif ($action == 'rejected') {
                    $orderReward->status = 'pesanan ditolak';
                    $orderReward->status_description = $request->input('body', 'Pesanan hadiah Anda ditolak');
                    $orderReward->icon_status = 'ic_status_canceled';

                    $notificationData = [
                        'title' => 'Pesanan Hadiah Anda Ditolak',
                        'body' => $request->input('body', 'Maaf, pesanan hadiah Anda telah ditolak. Jika Anda merasa ada kesalahan, silakan hubungi kami.'),
                        'route' => 'detail_order_reward',
                    ];
                    $user = $orderReward->user;
                    if ($user) {
                        $point = Point::firstOrNew(['user_id' => $user->id]);
                        $point->point += $orderReward->total_points; // Menambahkan kembali total_points ke saldo pengguna
                        $point->save();
                    }
                }
                $orderReward->save();
                $user = $orderReward->user;
            }
        }

        if ($user && $notificationData) {
            $fcmToken = $user->fcm_token;
            $notif = $this->notification($fcmToken, $notificationData['title'], $notificationData['body'], $notificationData['route'], $id);

            return response()->json([
                'message' => 'Order status berhasil diubah',
                'order' => $order ? new OrderResource($order) : new OrderRewardResource($orderReward),
                'notification' => $notif,
            ], 200);
        }

        return response()->json([
            'message' => 'Order tidak ditemukan atau tidak dalam status "menunggu konfirmasi".',
        ], 404);
    }

    public function updateStatusOrderSiap(Request $request, $id)
    {
        $action = $request->query('action');

        if (! $action || ! in_array($action, ['accepted', 'rejected'])) {
            return response()->json([
                'message' => 'Tidak ada nilai action yang valid.',
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
                $route = 'detail_common_order';
            } elseif ($action == 'rejected') {
                $order->status = 'pesanan dibatalkan';
                $order->status_description = 'Pesanan Anda dibatalkan';
                $order->icon_status = 'ic_status_canceled';

                $title = 'Pesanan Anda Dibatalkan';
                $body = 'Maaf, pesanan Anda telah dibatalkan. Jika Anda merasa ada kesalahan, silakan hubungi kami.';
                $route = 'detail_common_order';
            }
            $order->save();
            $user = $order->user;

            $notif = $this->notification($user->fcm_token, $title, $body, $route, $order->id);

            return response()->json([
                'message' => 'Order status berhasil diubah',
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
                $route = 'detail_order_reward';
            } elseif ($action == 'rejected') {
                $orderReward->status = 'pesanan dibatalkan';
                $orderReward->status_description = 'Pesanan hadiah Anda dibatalkan';
                $orderReward->icon_status = 'ic_status_canceled';

                $title = 'Pesanan Hadiah Anda Dibatalkan';
                $body = 'Maaf, pesanan hadiah Anda telah dibatalkan. Jika Anda merasa ada kesalahan, silakan hubungi kami.';
                $route = 'detail_order_reward';
            }
            $orderReward->save();
            $user = $orderReward->user;

            $notif = $this->notification($user->fcm_token, $title, $body, $route, $orderReward->id);

            return response()->json([
                'message' => 'Order status berhasil diubah',
                'order' => new OrderRewardResource($orderReward),
                'notification' => $notif,
            ], 200);
        }

        return response()->json([
            'message' => 'Order tidak ditemukan atau tidak dalam status "pesanan diproses".',
        ], 404);
    }

    public function updateStatusOrderSelesai(Request $request, $id)
    {
        $action = $request->query('action');

        if (! $action || ! in_array($action, ['accepted', 'rejected'])) {
            return response()->json([
                'message' => 'Tidak ada nilai action yang valid.',
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
                $route = 'detail_order_common';
            } elseif ($action == 'rejected') {
                $order->status = 'pesanan dibatalkan';
                $order->status_description = 'Pesanan Anda dibatalkan';
                $order->icon_status = 'ic_status_canceled';

                $title = 'Pesanan Anda Dibatalkan';
                $body = 'Maaf, pesanan Anda telah dibatalkan. Jika Anda merasa ada kesalahan atau ingin melakukan pemesanan ulang, silakan hubungi kami.';
                $route = 'detail_order_common';
            }
            $order->save();
            $user = $order->user;

            $notif = $this->notification($user->fcm_token, $title, $body, $route, $order->id);

            return response()->json([
                'message' => 'Order status berhasil diubah',
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
                $route = 'detail_order_reward';
            } elseif ($action == 'rejected') {
                $orderReward->status = 'pesanan dibatalkan';
                $orderReward->status_description = 'Pesanan hadiah Anda dibatalkan';
                $orderReward->icon_status = 'ic_status_canceled';

                $title = 'Pesanan Hadiah Anda Dibatalkan';
                $body = 'Maaf, pesanan hadiah Anda telah dibatalkan. Jika Anda merasa ada kesalahan atau ingin melakukan pemesanan ulang, silakan hubungi kami.';
                $route = 'detail_order_reward';
            }
            $orderReward->save();
            $user = $orderReward->user;

            $notif = $this->notification($user->fcm_token, $title, $body, $route, $orderReward->id);

            return response()->json([
                'message' => 'Order status berhasil diubah',
                'order' => new OrderRewardResource($orderReward),
                'notification' => $notif,
            ], 200);
        }

        return response()->json([
            'message' => 'Order tidak ditemukan atau tidak dalam status "pesanan siap diambil".',
        ], 404);
    }

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
}
