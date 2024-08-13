<?php

namespace App\Http\Controllers;

use App\Http\Requests\OrderRequest;
use App\Http\Resources\CartResource;
use App\Http\Resources\OrderResource;
use App\Http\Resources\OrderRewardResource;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderReward;
use App\Models\Product;
use App\Models\User;
use App\Models\UserVoucher;
use App\Models\Voucher;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Laravel\Firebase\Facades\Firebase;

class OrderController extends Controller
{
    public function notification(Request $request, $id)
    {
        $FcmToken = User::find($id)->fcm_token;
        $order = Order::find($request->order_id);

        if (! $order) {
            return response()->json(['message' => 'Order not found.'], 404);
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
        $statusOrder = $request->query('status_order');
        $startDate = $request->query('start_date');
        $endDate = $request->query('end_date');

        $ongoingStatuses = ['menunggu pembayaran', 'menunggu konfirmasi', 'pesanan diproses', 'pesanan siap diambil'];
        $historyStatuses = ['pesanan selesai', 'pesanan dibatalkan', 'pesanan ditolak'];

        $query = Order::where('user_id', $user->id)->with('payment')->orderBy('created_at', 'desc');

        if ($filterType === 'ongoing') {
            $query->whereIn('status', $ongoingStatuses);
        } elseif ($filterType === 'history') {
            $query->whereIn('status', $historyStatuses);
        }

        if ($statusOrder === 'finished') {
            $query->where('status', 'pesanan selesai');
        } elseif ($statusOrder === 'canceled') {
            $query->whereIn('status', ['pesanan dibatalkan', 'pesanan ditolak']);
        }

        if ($startDate && $endDate) {
            $start = Carbon::parse($startDate)->startOfDay();
            $end = Carbon::parse($endDate)->endOfDay();
            $query->whereBetween('created_at', [$start, $end]);
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
        $order->icon_status = 'ic_status_waiting';
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

        $user = null;
        $notification = new Request([
            'title' => 'Selesaikan Pembayaran Anda',
            'body' => 'Kami perhatikan bahwa Anda belum menyelesaikan pembayaran untuk pesanan Anda. Silakan selesaikan pembayaran secepatnya untuk memastikan pesanan Anda dapat diproses.',
            'route' => 'detail_order_common',
            'order_id' => $order->id,
        ]);

        $user = $order->user;
        $notif = $this->notification($notification, $user->id);

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
            'notification' => $notif,
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

        if ($order) {
            if ($action == 'accepted') {
                $order->status = 'pesanan diproses';
                $order->icon_status = 'ic_status_waiting';

                $notification = new Request([
                    'title' => 'Pesanan Anda Sedang Diproses',
                    'body' => 'Terima kasih telah melakukan pemesanan! Pesanan Anda saat ini sedang diproses dan akan segera dikirimkan. Kami akan mengupdate status pesanan Anda jika ada perubahan.',
                    'route' => '',
                ]);
            } elseif ($action == 'rejected') {
                $order->status = 'pesanan ditolak';
                $order->icon_status = 'ic_status_canceled';

                $notification = new Request([
                    'title' => 'Pesanan Anda Ditolak',
                    'body' => 'Maaf, pesanan Anda telah ditolak. Jika Anda merasa ada kesalahan, silakan hubungi kami.',
                    'route' => '',
                ]);
            }
            $order->save();
            $user = $order->user;

            $this->notification($notification, $user->id);

            return response()->json([
                'message' => 'Order status updated successfully.',
                'order' => new OrderResource($order),
            ], 200);
        }

        $orderReward = OrderReward::where('id', $id)->where('status', 'menunggu konfirmasi')->first();

        if ($orderReward) {
            if ($action == 'accepted') {
                $orderReward->status = 'pesanan diproses';
                $orderReward->icon_status = 'ic_status_waiting';

                $notification = new Request([
                    'title' => 'Pesanan Anda Sedang Diproses',
                    'body' => 'Terima kasih telah melakukan pemesanan! Pesanan Anda saat ini sedang diproses dan akan segera dikirimkan. Kami akan mengupdate status pesanan Anda jika ada perubahan.',
                    'route' => '',
                ]);
            } elseif ($action == 'rejected') {
                $orderReward->status = 'pesanan ditolak';
                $orderReward->icon_status = 'ic_status_canceled';

                $notification = new Request([
                    'title' => 'Pesanan Anda Ditolak',
                    'body' => 'Maaf, pesanan hadiah Anda telah ditolak. Jika Anda merasa ada kesalahan, silakan hubungi kami.',
                    'route' => '',
                ]);
            }
            $orderReward->save();
            $user = $orderReward->user;

            $this->notification($notification, $user->id);

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
        $user = null;

        if ($order) {
            if ($action == 'accepted') {
                $order->status = 'pesanan siap diambil';
                $order->icon_status = 'ic_status_ready';

                $notification = new Request([
                    'title' => 'Pesanan Anda Siap Diambil',
                    'body' => 'Pesanan Anda saat ini siap untuk diambil. Silakan ambil pesanan Anda sesuai dengan informasi yang diberikan.',
                    'route' => '',
                ]);
            } elseif ($action == 'rejected') {
                $order->status = 'pesanan dibatalkan';
                $order->icon_status = 'ic_status_canceled';

                $notification = new Request([
                    'title' => 'Pesanan Anda Dibatalkan',
                    'body' => 'Maaf, pesanan Anda telah dibatalkan. Jika Anda merasa ada kesalahan, silakan hubungi kami.',
                    'route' => '',
                ]);
            }
            $order->save();
            $user = $order->user;

            $this->notification($notification, $user->id);

            return response()->json([
                'message' => 'Order status updated successfully.',
                'order' => new OrderResource($order),
            ], 200);
        }

        $orderReward = OrderReward::where('id', $id)->where('status', 'pesanan diproses')->first();

        if ($orderReward) {
            if ($action == 'accepted') {
                $orderReward->status = 'pesanan siap diambil';
                $orderReward->icon_status = 'ic_status_ready';

                $notification = new Request([
                    'title' => 'Pesanan Hadiah Anda Siap Diambil',
                    'body' => 'Pesanan hadiah Anda saat ini siap untuk diambil. Silakan ambil pesanan hadiah Anda sesuai dengan informasi yang diberikan.',
                    'route' => '',
                ]);
            } elseif ($action == 'rejected') {
                $orderReward->status = 'pesanan dibatalkan';
                $orderReward->icon_status = 'ic_status_canceled';

                $notification = new Request([
                    'title' => 'Pesanan Hadiah Anda Dibatalkan',
                    'body' => 'Maaf, pesanan hadiah Anda telah dibatalkan. Jika Anda merasa ada kesalahan, silakan hubungi kami.',
                    'route' => '',
                ]);
            }
            $orderReward->save();
            $user = $orderReward->user;

            $this->notification($notification, $user->id);

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
        $user = null;

        if ($order) {
            if ($action == 'accepted') {
                $order->status = 'pesanan selesai';
                $order->icon_status = 'ic_status_done';

                $notification = new Request([
                    'title' => 'Pesanan Anda Selesai',
                    'body' => 'Pesanan Anda telah selesai. Terima kasih telah berbelanja dengan kami. Kami berharap Anda puas dengan layanan kami.',
                    'route' => '',
                ]);
            } elseif ($action == 'rejected') {
                $order->status = 'pesanan dibatalkan';
                $order->icon_status = 'ic_status_canceled';

                $notification = new Request([
                    'title' => 'Pesanan Anda Dibatalkan',
                    'body' => 'Maaf, pesanan Anda telah dibatalkan. Jika Anda merasa ada kesalahan atau ingin melakukan pemesanan ulang, silakan hubungi kami.',
                    'route' => '',
                ]);
            }
            $order->save();
            $user = $order->user;

            $this->notification($notification, $user->id);

            return response()->json([
                'message' => 'Order status updated successfully.',
                'order' => new OrderResource($order),
            ], 200);
        }

        $orderReward = OrderReward::where('id', $id)->where('status', 'pesanan siap diambil')->first();

        if ($orderReward) {
            if ($action == 'accepted') {
                $orderReward->status = 'pesanan selesai';
                $orderReward->icon_status = 'ic_status_done';

                $notification = new Request([
                    'title' => 'Pesanan Hadiah Anda Selesai',
                    'body' => 'Pesanan hadiah Anda telah selesai. Terima kasih telah berbelanja dengan kami. Kami berharap Anda puas dengan layanan kami.',
                    'route' => '',
                ]);
            } elseif ($action == 'rejected') {
                $orderReward->status = 'pesanan dibatalkan';
                $orderReward->icon_status = 'ic_status_canceled';

                $notification = new Request([
                    'title' => 'Pesanan Hadiah Anda Dibatalkan',
                    'body' => 'Maaf, pesanan hadiah Anda telah dibatalkan. Jika Anda merasa ada kesalahan atau ingin melakukan pemesanan ulang, silakan hubungi kami.',
                    'route' => '',
                ]);
            }
            $orderReward->save();
            $user = $orderReward->user;

            $this->notification($notification, $user->id);

            return response()->json([
                'message' => 'Order status updated successfully.',
                'order' => new OrderRewardResource($orderReward),
            ], 200);
        }

        return response()->json([
            'message' => 'Order not found or not in the "pesanan siap diambil" status.',
        ], 404);
    }

    public function reorder($orderId)
    {
        $userId = Auth::id();

        $previousOrder = Order::with('orderItems')->where('id', $orderId)->where('user_id', $userId)->first();

        if (! $previousOrder) {
            return response()->json([
                'message' => 'Order not found or not authorized.',
            ], 404);
        }

        $cart = Cart::firstOrCreate(['user_id' => $userId]);

        $cart->cartItems()->delete();

        foreach ($previousOrder->orderItems as $orderItem) {
            $cartItem = new CartItem();
            $cartItem->cart_id = $cart->id;
            $cartItem->product_id = $orderItem->product_id;
            $cartItem->temperatur = $orderItem->temperatur;
            $cartItem->ice = $orderItem->ice;
            $cartItem->size = $orderItem->size;
            $cartItem->sugar = $orderItem->sugar;
            $cartItem->note = $orderItem->note;
            $cartItem->quantity = $orderItem->quantity;
            $cartItem->price = $orderItem->price;
            $cartItem->save();
        }

        return response()->json([
            'message' => 'Items successfully added to cart.',
            'cart' => new CartResource($cart),
        ], 200);
    }
}
