<?php

namespace App\Http\Resources;

use App\Models\Tags;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CategoryResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {

        $tags = Tags::whereIn('id',explode(',',$this->tags))->select('name')->get();
        return [
            'id' => $this->id,
            'name' =>  $this->getTranslation('name', app()->getLocale()),
            'tags' => $tags->map(function ($tag) {
                return $tag->getTranslation('name', app()->getLocale());
            }),
            // 'parent' => $this->parent->getTranslation('name', app()->getLocale()),
            'image' => env('APP_URL'). '/' . $this->image,
            'status'=> $this->status,
            'created_at' => $this->created_at->format('Y-m-d'),
            'updated_at' => $this->updated_at->format('Y-m-d')
        ];
    }
}
