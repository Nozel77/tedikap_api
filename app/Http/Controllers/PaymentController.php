<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Payment;
use App\Models\Point;
use App\Models\User;
use App\Models\UserVoucher;
use App\Models\Voucher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Laravel\Firebase\Facades\Firebase;
use Xendit\Configuration;
use Xendit\Invoice\CreateInvoiceRequest;
use Xendit\Invoice\InvoiceApi;

class PaymentController extends Controller
{
    public function __construct()
    {
        Configuration::setXenditKey(env('XENDIT_API_KEY'));
        $this->apiInstance = new InvoiceApi();
    }

    public function notification(array $notification, $id, $orderId)
    {
        $FcmToken = User::find($id)->fcm_token;
        $order = Order::find($orderId);

        $message = CloudMessage::fromArray([
            'token' => $FcmToken,
            'notification' => [
                'title' => $notification['title'],
                'body' => $notification['body'],
            ],
        ])->withData([
            'route' => $notification['route'],
            'order_id' => $orderId,
        ]);

        Firebase::messaging()->send($message);

        return $message;
    }

    protected function notifyAdminsAboutNewOrder($order)
    {
        $adminIds = User::where('role', 'admin')->pluck('id');

        foreach ($adminIds as $adminId) {
            $notificationData = [
                'title' => 'Pesanan Baru - Menunggu Konfirmasi',
                'body' => "Pesanan baru dengan ID: {$order->id} sekarang menunggu konfirmasi. Silakan periksa pesanan baru di sistem admin.",
                'route' => '/orders',
            ];

            $this->notification($notificationData, $adminId, $order->id);
        }
    }

    public function store(Request $request)
    {
        $user = Auth::user();

        $order = Order::where('user_id', $user->id)
            ->where('status', 'menunggu pembayaran')
            ->latest()
            ->first();

        if (! $order) {
            return response()->json([
                'error' => 'Order not found or no pending orders for the authenticated user.',
            ], 404);
        }

        $payer_email = $user->email;

        $create_invoice_request = new CreateInvoiceRequest([
            'external_id' => (string) Str::uuid(),
            'description' => 'Pembayaran untuk pesanan '.$order->id,
            'amount' => $order->total_price,
            'payer_email' => $payer_email,
            'invoice_duration' => 120,
            'payment_methods' => ['OVO', 'DANA', 'SHOPEEPAY', 'LINKAJA'],
        ]);

        $result = $this->apiInstance->createInvoice($create_invoice_request);

        $payment = new Payment();
        $payment->status = 'menunggu pembayaran';
        $payment->checkout_link = $result['invoice_url'];
        $payment->external_id = $create_invoice_request['external_id'];
        $payment->user_id = $user->id;
        $payment->amount = $order->total_price;
        $payment->payment_channel = null;
        $payment->order_id = $order->id;
        $payment->invoice_duration = 60;
        $payment->save();

        $order->link_invoice = $result['invoice_url'];
        $order->save();

        return response()->json($payment);
    }

    public function webhook(Request $request)
    {
        $request->validate([
            'external_id' => 'required|exists:payments,external_id',
            'status' => 'required|string',
            'payment_channel' => 'nullable|string',
        ]);

        $payment = Payment::with(['user', 'order'])->where('external_id', $request->external_id)->firstOrFail();

        if (strtolower($payment->status) === 'paid') {
            return response()->json('Payment has already been processed', 200);
        }

        $payment->status = strtolower($request->status);
        $payment->payment_channel = $request->payment_channel;
        $payment->save();

        if (strtolower($request->status) === 'paid') {
            $additionalPoints = floor($payment->amount / 3000);
            $additionalPoints += ($payment->amount % 3000 == 0) ? 0 : 1;

            if ($additionalPoints > 0) {
                $point = Point::firstOrCreate(['user_id' => $payment->user_id]);
                $point->point += $additionalPoints;
                $point->save();
            }

            $order = $payment->order;
            if ($order) {
                $order->payment_channel = $payment->payment_channel;
                $order->status = 'menunggu konfirmasi';
                $order->status_description = 'Pembayaran selesai, pesanan sedang menunggu konfirmasi';
                $order->save();

                $this->notifyAdminsAboutNewOrder($order);

                $notification = [
                    'title' => 'Pembayaran Selesai - Menunggu Konfirmasi',
                    'body' => "Terima kasih telah menyelesaikan pembayaran untuk pesanan Anda (ID: {$payment->order->id}). Pesanan Anda sekarang sedang menunggu konfirmasi dari admin. Kami akan segera memprosesnya dan memberi tahu Anda jika ada pembaruan lebih lanjut.",
                    'route' => 'detail_order_common',
                ];

                $user = $payment->user;
                $this->notification($notification, $user->id, $payment->order->id);
            }

        } elseif (strtolower($request->status) === 'expired') {
            $order = $payment->order;
            if ($order) {
                $order->status = 'pesanan dibatalkan';
                $order->status_description = 'Pembayaran gagal atau kadaluwarsa';
                $order->icon_status = 'ic_status_canceled';
                $order->save();

                $notification = [
                    'title' => 'Pembayaran Kadaluarsa',
                    'body' => "Pembayaran untuk pesanan Anda (ID: {$payment->order->id}) telah kadaluwarsa dan pesanan Anda dibatalkan. Silakan lakukan pemesanan ulang jika Anda masih ingin melanjutkan.",
                    'route' => 'detail_order_common',
                    'order_id' => $payment->order->id,
                ];

                $user = $payment->user;
                $this->notification($notification, $user->id, $payment->order->id);

                if ($order->voucher_id) {
                    $userVoucher = UserVoucher::where('user_id', $payment->user_id)
                        ->where('voucher_id', $order->voucher_id)
                        ->first();

                    if ($userVoucher) {
                        $voucher = Voucher::find($order->voucher_id);
                        if ($voucher) {
                            $voucher->is_used = false;
                            $voucher->save();
                        }

                        $userVoucher->used = false;
                        $userVoucher->save();
                    }
                }
            }
        }

        return response()->json(['message' => 'Payment status updated successfully'], 200);
    }
}
