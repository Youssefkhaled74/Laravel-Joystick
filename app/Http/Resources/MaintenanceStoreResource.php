<?php

namespace App\Http\Resources;

use App\Models\Tags;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Resources\Json\JsonResource;

class MaintenanceStoreResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $tags = Tags::whereIn('id', explode(',', $this->tags))->select('name')->get();
        return [
            'id' => $this->id ?? null,
            'name' =>  $this->getTranslation('name', app()->getLocale()) ?? null,
            'description' =>  $this->getTranslation('description', app()->getLocale()) ?? null,
            'price' => $this->price ?? null,
            'quantity' => $this->quantity ?? null,
            'repair_category' => $this->RepairCategory->name ?? null,
            'tags' => $tags->map(function ($tag) {
                return $tag->getTranslation('name', app()->getLocale());
            }) ?? null,
            'image' => env('APP_URL'). '/public/' . $this->image ?? null,
            'created_at' => $this->created_at ? $this->created_at->format('Y-m-d') : null,
            'updated_at' => $this->updated_at ? $this->updated_at->format('Y-m-d') : null,
        ];
    }
}
