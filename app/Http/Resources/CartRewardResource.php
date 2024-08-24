<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CartRewardResource extends JsonResource
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
            'user_id' => $this->user_id,
            'total_points' => $this->total_points,
            'schedule_pickup' => $this->schedule_pickup,
            'session_1' => '9.40-10.00',
            'session_2' => '12.00-12.30',
            'points_enough' => $this->points_enough,
            'is_phone' => $this->is_phone,
            'cart_items' => CartRewardItemResource::collection($this->rewardCartItems),
        ];
    }
}
