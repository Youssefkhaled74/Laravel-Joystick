<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class HistoryRepairRequestResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'status' => $this->status,
            'created_at' => $this->created_at,
            'devices' => $this->devices->map(function ($device) {
                return [
                    'device_name' => $device->device->name,
                    'problem_parts' => json_decode($device->problem_parts),
                    'notes' => $device->notes,
                ];
            }),
        ];
    }
}