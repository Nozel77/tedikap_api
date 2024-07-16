<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Payment;
use App\Models\Point;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
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

    public function store(Request $request)
    {
        $user = Auth::user();

        $order = Order::where('user_id', $user->id)
            ->where('status', 'ongoing')
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
            'description' => 'checkout demo',
            'amount' => $order->total_price,
            'payer_email' => $payer_email,
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
        $payment->save();

        return response()->json($payment);
    }

    public function webhook(Request $request)
    {
        $request->validate([
            'external_id' => 'required|exists:payments,external_id',
            'status' => 'required|string',
            'payment_channel' => 'required|string',
        ]);

        $payment = Payment::where('external_id', $request->external_id)->firstOrFail();

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
                $order->save();
            }
        }

        return response()->json(['message' => 'Payment status updated successfully'], 200);
    }
}
