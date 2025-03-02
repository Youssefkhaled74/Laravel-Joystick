<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AddressResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {

        if ($this->key == "Home") {
            $key = __('messages.Home');
        } elseif ($this->key == "Work") {
            $key = __('messages.Work');
        } else {
            $key = __('messages.Undefiend');
        }
        return [
            'id' => $this->id,
            'grand_address'=> $this->apartment_number. ',' . $this->floor_number. ',' . $this->building_number. ',' . $this->address,
            'address' => $this->address,
            "user_id" => $this->user_id,
            "is_main" => $this->is_main,
            "area" => AreaResource::make($this->area),
            "apartment_number" => $this->apartment_number,
            "building_number" => $this->building_number,    
            "floor_number" => $this->floor_number,
            "key" => $key,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'address_link' => $this->address_link,
            "created_at" => $this->created_at,
            "updated_at"=> $this->updated_at
        ];
    }
}
