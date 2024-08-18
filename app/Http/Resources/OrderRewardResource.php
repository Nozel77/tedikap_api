<?php

namespace App\Http\Resources;

use Carbon\Carbon;
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

        $cartHasItems = $this->cartReward ? $this->cartReward->rewardCartItems->isNotEmpty() : false;

        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'name' => $this->user->name,
            'avatar' => $this->user->avatar,
            'cart_reward_id' => $this->cart_reward_id,
            'total_point' => $this->total_point,
            'status' => $this->status,
            'status_description' => $this->status_description,
            'whatsapp' => $this->whatsapp,
            'icon_status' => $this->icon_status.'.svg',
            'order_type' => $this->order_type,
            'schedule_pickup' => $this->schedule_pickup,
            'cart_length' => $cartHasItems,
            'rating' => $this->rating,
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at->format('Y-m-d H:i:s'),
            'expires_at' => Carbon::parse($this->expires_at)->format('Y-m-d H:i:s'),
            'order_reward_items' => OrderRewardItemsResource::collection($this->orderRewardItems),
        ];
    }
}
