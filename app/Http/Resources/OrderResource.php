<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {

        $original_price = $this->total_price + $this->discount_amount;

        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'cart_id' => $this->cart_id,
            'voucher_id' => $this->voucher_id,
            'total_price' => $this->total_price,
            'discount_amount' => $this->discount_amount,
            'original_price' => $original_price,
            'status' => $this->status,
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at->format('Y-m-d H:i:s'),
            'order_items' => OrderItemsResource::collection($this->orderItems),
        ];
    }
}
