<?php

namespace App\Http\Controllers;

use App\Http\Requests\OrderRequest;
use App\Http\Resources\CartResource;
use App\Http\Resources\OrderResource;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\PointConfiguration;
use App\Models\Product;
use App\Models\UserVoucher;
use App\Models\Voucher;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Laravel\Firebase\Facades\Firebase;

class OrderController extends Controller
{
    protected $statusStoreService;

    public function __construct(StatusStoreController $statusStoreService)
    {
        $this->statusStoreService = $statusStoreService;
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

        if ($statusOrder) {
            if (in_array($statusOrder, ['pesanan selesai', 'pesanan dibatalkan', 'pesanan ditolak'])) {
                $query->where('status', $statusOrder);
            } elseif ($statusOrder === 'finished_rejected') {
                $query->whereIn('status', ['pesanan selesai', 'pesanan ditolak']);
            } elseif ($statusOrder === 'canceled_rejected') {
                $query->whereIn('status', ['pesanan dibatalkan', 'pesanan ditolak']);
            } elseif ($statusOrder === 'finished_canceled') {
                $query->whereIn('status', ['pesanan selesai', 'pesanan dibatalkan']);
            }
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

            $order->schedule_pickup = $this->statusStoreService->storeStatus()->getData($time)->data->time;

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

        $user = Auth::user();
        if (empty($user->whatsapp_number)) {
            return response()->json([
                'message' => 'Nomor WhatsApp harus diisi sebelum melakukan order.',
            ], 400);
        }

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

        $pointConfig = PointConfiguration::all()->first;
        $minimumAmount = $pointConfig->minimum_amount ?? 5000;
        $collectPoint = $pointConfig->collect_point ?? 1000;

        $additionalPoints = floor($totalPrice / $minimumAmount);
        $additionalPoints += ($totalPrice % $minimumAmount == 0) ? 0 : $collectPoint;

        $order = new Order();
        $order->id = $this->generateCustomUUID();
        $order->user_id = $userId;
        $order->cart_id = $cart->id;
        $order->voucher_id = $cart->voucher_id;
        $order->total_price = $totalPrice;
        $order->discount_amount = $discountAmount;
        $order->reward_point = $additionalPoints;
        $order->status = 'menunggu pembayaran';
        $order->status_description = 'mohon segera lakukan pembayaran supaya pesanan Anda dapat diproses';
        $whatsappMessage = urlencode("halo saya ingin tanya tentang pesanan saya dengan id {$order->id}");
        $order->whatsapp = "https://wa.me/62895395343223?text={$whatsappMessage}";
        $order->whatsapp_user = "https://wa.me/62{$user->whatsapp_number}";
        $order->expires_at = now()->addMinutes(5);
        $order->order_type = 'order';
        $order->icon_status = 'ic_status_waiting';
        $order->save();

        $createdAt = now()->setTimezone('Asia/Jakarta');
        $time = $createdAt->format('H:i');
        $pickupTime = $this->statusStoreService->storeStatus()->getData($time)->data->time;

        $order->schedule_pickup = $pickupTime;
        $order->save();

        $user = $order->user;
        $notification = [
            'title' => 'Selesaikan Pembayaran Anda',
            'body' => 'Kami perhatikan bahwa Anda belum menyelesaikan pembayaran untuk pesanan Anda. Silakan selesaikan pembayaran secepatnya untuk memastikan pesanan Anda dapat diproses.',
            'route' => 'detail_order_common',
            'order_id' => $order->id,
        ];

        $notif = $this->notification($user->fcm_token, $notification['title'], $notification['body'], $notification['route'], $order->id);

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
}
