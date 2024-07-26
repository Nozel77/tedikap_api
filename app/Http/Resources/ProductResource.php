<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Auth;

class ProductResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $user_id = Auth::id();
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'regular_price' => $this->regular_price,
            'large_price' => $this->large_price,
            'category' => $this->category,
            'image' => $this->image,
            'isLiked' => $user_id ? $this->favorites()->where('user_id', $user_id)->exists() : false,
        ];
    }
}
