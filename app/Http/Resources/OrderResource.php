<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use App\Http\Resources\OrderDetailsResource;
use App\Models\Address;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
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
            'order_number' => $this->order_number,
            'user' => $this->user->username,
            'address_id' => AddressResource::make(Address::find($this->address_id)),
            'total' => $this->total,
            'payment_method' => $this->payment_method,
            'status' => $this->status,
            'created_at' => $this-> created_at,
            'updated_at' => $this-> updated_at,
            'order_detalis' => OrderDetailsResource::collection($this->items)
        ];
    }
}
