<?php

namespace App\Http\Controllers;

use App\Http\Requests\OrderRequest;
use App\Models\Order;

class OrderController extends Controller
{
    public function index()
    {
        $data = Order::all();

        return $this->resShowData($data);
    }

    public function store(OrderRequest $request)
    {
        $request->validated();

        $data = new Order([
            'user_id' => $request->user_id,
            'product_id' => $request->product_id,
            'promo_id' => $request->promo_id,
            'temperatur' => $request->temperatur,
            'size' => $request->size,
            'ice' => $request->ice,
            'sugar' => $request->sugar,
            'note' => $request->note,
            'quantity' => $request->quantity,
            'total' => $request->total,
        ]);
        $data->save();

        return $this->resShowData($data);

    }

    public function show($id)
    {
        $data = Order::find($id);
        if (! $data) {
            return $this->resDataNotFound('Order');
        }

        return $this->resShowData($data);
    }

    public function update(OrderRequest $request, $id)
    {
        $request->validated();

        $data = Order::find($id);
        if (! $data) {
            return $this->resDataNotFound('Order');
        }

        $data->update([
            'user_id' => $request->user_id,
            'product_id' => $request->product_id,
            'promo_id' => $request->promo_id,
            'temperatur' => $request->temperatur,
            'size' => $request->size,
            'ice' => $request->ice,
            'sugar' => $request->sugar,
            'note' => $request->note,
            'quantity' => $request->quantity,
            'total' => $request->total,
        ]);

        return $this->resUpdatedData($data);
    }

    public function destroy($id)
    {
        $data = Order::find($id);
        if (! $data) {
            return $this->resDataNotFound('Order');
        }
        $data->delete();

        return $this->resDataDeleted();
    }
}
