<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Xendit\Configuration;
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
        $create_invoice_request = new \Xendit\Invoice\CreateInvoiceRequest([
            'external_id' => (string) Str::uuid(),
            'description' => $request->description,
            'amount' => $request->amount,
            'payer_email' => $request->payer_email,
        ]);

        $result = $this->apiInstance->createInvoice($create_invoice_request);

        $payment = new Payment();
        $payment->status = 'PENDING';
        $payment->checkout_link = $result['invoice_url'];
        $payment->external_id = $create_invoice_request['external_id'];
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
}
