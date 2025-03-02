<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderDetailsResource extends JsonResource
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
            'order_id' => $this->order_id,
            'product_name' => $this->product ? $this->product->getTranslation('name', app()->getLocale()) : null,
            'quantity' => $this->quantity,
            'price' => $this->product ? $this->product->price : 0,
            'total' => $this->product ? $this->product->price * $this->quantity : 0,
            'created_at' => $this->created_at->format('Y-m-d'),
            'updated_at' => $this-> updated_at->format('Y-m-d'),
        ];
    }
}
