<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RewardProductResource extends JsonResource
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
            'name' => $this->name,
            'description' => $this->description,
            'regular_point' => $this->regular_point,
            'large_point' => $this->large_point,
            'category' => $this->category,
            'image' => $this->image,
            'stock' => $this->stock,
        ];
    }
}
