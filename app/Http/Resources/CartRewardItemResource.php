<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CartRewardItemResource extends JsonResource
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
            'product_id' => $this->reward_product_id,
            'product_name' => $this->rewardProduct->name,
            'product_image' => $this->rewardProduct->image,
            'stock' => $this->rewardProduct->stock,
            'temperatur' => $this->temperatur,
            'size' => $this->size,
            'ice' => $this->ice,
            'sugar' => $this->sugar,
            'note' => $this->note,
            'quantity' => $this->quantity,
            'points' => $this->points,
            'total_points' => $this->quantity * $this->points,
        ];
    }
}
