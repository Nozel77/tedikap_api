<?php

namespace App\Http\Controllers;

use App\Http\Requests\OrderRequest;
use App\Models\Order;

class OrderController extends Controller
{
    public function index()
    {
        $data = Order::all();

        return response()->json([
            'status' => 'success',
            'message' => 'get data success',
            'data' => $data,
        ]);
    }

    public function store(OrderRequest $request)
    {
        $request->validated();

        $order = new Order([
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
        $order->save();

        return response()->json([
            'success' => true,
            'message' => 'Order created successfully',
            'data' => $order,
        ], 201);

    }

    public function show($id)
    {
        $data = Order::find($id);
        if (! $data) {
            return response()->json([
                'status' => 'error',
                'message' => 'Order not found',
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'get data successfully',
            'data' => $data,
        ]);
    }

    public function update(OrderRequest $request, $id)
    {
        $request->validated();
        $order = Order::find($id);
        if (! $order) {
            return response()->json([
                'status' => 'error',
                'message' => 'Order not found',
            ], 404);
        }

        $order->update([
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

        return response()->json([
            'status' => 'success',
            'message' => 'Order updated successfully',
            'data' => $order,
        ]);
    }

    public function destroy($id)
    {
        $order = Order::find($id);
        if (! $order) {
            return response()->json([
                'status' => 'error',
                'message' => 'Order not found',
            ], 404);
        }
        $order->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Order deleted successfully',
        ]);
    }
}
