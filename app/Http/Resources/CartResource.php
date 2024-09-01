<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CartResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $totalPrice = $this->cartItems->sum(function ($cartItem) {
            return $cartItem->quantity * $cartItem->price;
        });

        $rewardPoint = floor($totalPrice / 3000);
        $rewardPoint += ($totalPrice % 3000 == 0) ? 0 : 1;

        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'voucher_id' => $this->voucher_id,
            'total_price' => $this->total_price,
            'discount_amount' => $this->discount_amount,
            'original_price' => $this->original_price,
            'reward_point' => $rewardPoint,
            'schedule_pickup' => $this->schedule_pickup,
            'session_1' => $this->session_1,
            'session_2' => $this->session_2,
            'endSession_1' => $this->endSession_1,
            'endSession_2' => $this->endSession_2,
            'is_phone' => $this->is_phone,
            'cart_items' => CartItemResource::collection($this->cartItems),
        ];
    }
}
