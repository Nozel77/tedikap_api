<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReviewResource extends JsonResource
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
            'order_id' => $this->order_id,
            'name' => $this->user->name,
            'avatar' => $this->user->avatar,
            'staff_service' => $this->staff_service,
            'product_quality' => $this->product_quality,
            'average_rating' => $this->rating,
            'note' => $this->note,
        ];
    }
}
