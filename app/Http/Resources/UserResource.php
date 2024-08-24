<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
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
            'email' => $this->email,
            'name' => $this->name,
            'avatar' => $this->avatar,
            'gender' => $this->gender,
            'role' => $this->role,
            'whatsapp_number' => $this->whatsapp_number,
            'whatsapp_service' => $this->whatsapp_service,
            'fcm_token' => $this->fcm_token,
        ];
    }
}
