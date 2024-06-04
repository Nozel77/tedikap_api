<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\Payment;
use App\Models\Point;
use Illuminate\Http\Request;
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
        $request->validate([
            'cart_id' => 'required|exists:carts,id',
            'payer_email' => 'required|email',
            'user_id' => 'required|exists:users,id',
        ]);

        $cart = Cart::findOrFail($request->cart_id);

        $create_invoice_request = new CreateInvoiceRequest([
            'external_id' => (string) Str::uuid(),
            'description' => 'checkout demo',
            'amount' => $cart->total,
            'payer_email' => $request->payer_email,
        ]);

        $result = $this->apiInstance->createInvoice($create_invoice_request);

        $payment = new Payment();
        $payment->status = 'PENDING';
        $payment->checkout_link = $result['invoice_url'];
        $payment->external_id = $create_invoice_request['external_id'];
        $payment->user_id = $request->user_id;
        $payment->amount = $cart->total;
        $payment->save();

        return response()->json($payment);
    }

    public function notification(Request $request)
    {
        $result = $this->apiInstance->getInvoices(null, $request->external_id);

        $payment = Payment::where('external_id', $request->external_id)->firstOrFail();

        if ($payment->status == 'SETTLED') {
            return response()->json('payment anda telah diproses');
        }

        $payment->status = strtolower($result[0]['status']);
        $payment->save();

        return response()->json(['message' => 'success']);
    }

    public function paymentCallback(Request $request)
    {
        $request->validate([
            'external_id' => 'required|exists:payments,external_id',
            'status' => 'required|string',
        ]);

        $payment = Payment::where('external_id', $request->external_id)->firstOrFail();

        $payment->status = $request->status;
        $payment->save();

        if ($request->status == 'SUCCESS') {

            $additionalPoints = floor($payment->amount / 3000);

            $additionalPoints += ($payment->amount % 3000 == 0) ? 0 : 1;

            if ($additionalPoints > 0) {
                $point = Point::firstOrCreate(['user_id' => $payment->user_id]);
                $point->point += $additionalPoints;
                $point->save();
            }
        }

        return response()->json(['message' => 'Payment status updated successfully']);
    }
}
