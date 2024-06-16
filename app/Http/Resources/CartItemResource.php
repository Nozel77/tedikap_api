<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CartItemResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'product_id' => $this->product_id,
            'product_name' => $this->product->name,
            'temperatur' => $this->temperatur,
            'size' => $this->size,
            'ice' => $this->ice,
            'sugar' => $this->sugar,
            'note' => $this->note,
            'quantity' => $this->quantity,
            'price' => $this->price,
            'total_price' => $this->quantity * $this->price,
        ];
    }
}
