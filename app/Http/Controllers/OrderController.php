<?php

namespace App\Http\Controllers;

use App\Http\Requests\OrderRequest;
use App\Models\Cart;
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
        $data = $request->validated();

        $data['user_id'] = Cart::all()->where('id', $data['cart_id'])->first()->user_id;

        $order = new Order();

        $order->fill($data);
        $order->save();

        return $this->resShowData($order);
    }
}
