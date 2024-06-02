<?php

namespace App\Http\Controllers;

use App\Http\Requests\CartRequest;
use App\Models\Cart;
use Illuminate\Http\Request;

class CartController extends Controller
{
    public function index()
    {
        $data = Cart::all();

        return $this->resShowData($data);
    }

    public function store(CartRequest $request)
    {
        $request->validated();

        $data = new Cart([
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
        $data = Cart::find($id);
        if (! $data) {
            return $this->resDataNotFound('Cart');
        }

        return $this->resShowData($data);
    }

    public function update(CartRequest $request, $id)
    {
        $request->validated();

        $data = Cart::find($id);
        if (! $data) {
            return $this->resDataNotFound('Cart');
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
        $data = Cart::find($id);
        if (! $data) {
            return $this->resDataNotFound('Cart');
        }
        $data->delete();

        return $this->resDataDeleted();
    }
}
