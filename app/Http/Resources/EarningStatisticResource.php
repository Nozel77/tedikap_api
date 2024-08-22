<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EarningStatisticResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'total_sales' => $this->resource['total_sales'],
            'average_per_week' => $this->resource['average_per_week'],
            'earning_growth' => $this->resource['earning_growth'],
        ];
    }
}
