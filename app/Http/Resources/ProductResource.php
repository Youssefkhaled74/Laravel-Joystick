<?php

namespace App\Http\Resources;

use App\Models\Tags;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
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
            'small_description' =>  $this->getTranslation('small_description', app()->getLocale()) ?? null,
            'price' => $this->price ?? null,
            'quantity' => $this->quantity ?? null,
            'status' => $this->quantity == 1 ? 'Last Piece' : ($this->status ?? 'inactive'),
            'category' => $this->category->name ?? null,
            'brand' => $this->brand->name ?? null,
            // 'colors' => $this->colors ? explode(',', $this->colors) : [] ?? null,
            'tags' => $tags->map(function ($tag) {
                return $tag->getTranslation('name', app()->getLocale());
            }) ?? null,
            'main_image' => env('APP_URL') . '/public/' . $this->main_image ?? null,
            'images' => $this->images ? collect(explode(',', $this->images))->map(fn($image) => env('APP_URL') . '/public/' . $image) : [],
            'product_colors' => $this->colors()->get()->map(function ($color) {
                return [
                    'color' => $color->color,
                    'quantity' => $color->quantity,
                ];
            }),
            'is_favorite' => $this->isFavoritedByUser($request->user()) ?? null,
            'created_at' => $this->created_at ? $this->created_at->format('Y-m-d') : null,
            'updated_at' => $this->updated_at ? $this->updated_at->format('Y-m-d') : null,
        ];
    }
    public function isFavoritedByUser($user)
    {
        $user = User::find(Auth::id());
        if (! $user) {
            return 0;
        }

        return $user->favoriteProducts()->where('product_id', $this->id)->exists() ? 1 : 0 ?? null;
    }
}
