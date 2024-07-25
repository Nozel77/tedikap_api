<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderItemsResource extends JsonResource
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
            'product_image' => $this->product->image,
            'item_type' => $this->item_type,
            'temperatur' => $this->temperatur,
            'size' => $this->size,
            'ice' => $this->ice,
            'sugar' => $this->sugar,
            'note' => $this->note,
            'quantity' => $this->quantity,
            'price' => $this->price,
        ];
    }
}
