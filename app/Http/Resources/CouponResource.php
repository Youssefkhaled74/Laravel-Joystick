<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CouponResource extends JsonResource
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
            'code' => $this->code,
            'price' => $this->price,
            'status' => $this->status,
            'min_price' => $this->min_price,
            'user_id' => $this->user->username ?? null,
            'type' => $this->type,
        ];
    }
}
