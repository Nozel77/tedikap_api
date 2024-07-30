<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VoucherResource extends JsonResource
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
            'title' => $this->title,
            'description' => $this->description,
            'image' => $this->image,
            'discount' => $this->discount,
            'min_transaction' => $this->min_transaction,
            'max_discount' => $this->max_discount,
            'start_date' => $this->start_date,
            'end_date' => $this->end_date,
        ];
    }
}
