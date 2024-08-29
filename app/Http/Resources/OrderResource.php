<?php

namespace App\Http\Resources;

use Carbon\Carbon;
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
        $cartHasItems = $this->cart ? $this->cart->cartItems->isNotEmpty() : false;

        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'cart_id' => $this->cart_id,
            'name' => $this->user->name,
            'avatar' => $this->user->avatar,
            'voucher_id' => $this->voucher_id,
            'total_price' => $this->total_price,
            'discount_amount' => $this->discount_amount,
            'reward_point' => $this->reward_point,
            'original_price' => $original_price,
            'status' => $this->status,
            'status_description' => $this->status_description,
            'whatsapp' => $this->whatsapp,
            'whatsapp_user' => $this->whatsapp_user,
            'order_type' => $this->order_type,
            'schedule_pickup' => $this->schedule_pickup,
            'icon_status' => $this->icon_status.'.svg',
            'payment_channel' => $this->payment ? $this->payment->payment_channel : null,
            'cart_length' => $cartHasItems,
            'rating' => $this->rating,
            'link_invoice' => $this->link_invoice,
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at->format('Y-m-d H:i:s'),
            'expires_at' => Carbon::parse($this->expires_at)->format('Y-m-d H:i:s'),
            'order_items' => OrderItemsResource::collection($this->orderItems),
        ];
    }
}
