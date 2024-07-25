<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderRewardItemsResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $total_points = $this->points * $this->quantity;

        return [
            'id' => $this->id,
            'product_id' => $this->reward_product_id,
            'product_name' => $this->rewardProduct->name,
            'product_image' => $this->rewardProduct->image,
            'item_type' => $this->item_type,
            'temperatur' => $this->temperatur,
            'size' => $this->size,
            'ice' => $this->ice,
            'sugar' => $this->sugar,
            'note' => $this->note,
            'quantity' => $this->quantity,
            'points' => $this->points,
            'total_points' => $total_points,
        ];
    }
}
