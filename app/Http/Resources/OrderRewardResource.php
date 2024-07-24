<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderRewardResource extends JsonResource
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
            'cart_reward_id' => $this->cart_reward_id,
            'total_point' => $this->total_point,
            'status' => $this->status,
            'order_type' => $this->order_type,
            'schedule_pickup' => $this->schedule_pickup,
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at->format('Y-m-d H:i:s'),
            'order_reward_items' => OrderRewardItemsResource::collection($this->orderRewardItems),
        ];
    }
}
