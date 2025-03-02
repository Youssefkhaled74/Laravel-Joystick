<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Auth;


class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $imagePath = $this->profile_picture
        ? env('APP_URL') . '/public/' . $this->profile_picture 
        : null;
        return [
            'id' => $this->id,
            'username' => $this->username,
            'email' => $this->email,
            'email_verified' => $this->email_verified_at ? 1 : 0,
            'phone' => $this->phone,
            // 'image' => $this->profile_picture ? url($this->profile_picture) : url('default-profile-pic-url'),
            // 'image' => env('APP_URL'). '/public/' . $this->profile_picture ?? null,
            'image' => $imagePath,
            'created_at' => $this->created_at ? $this->created_at : null,
            'updated_at' => $this->updated_at ? $this->updated_at : null,
            'token' => $this->token,
            'status'=>$this->status,
            'addresses' => AddressResource::collection($this->addresses),
            'fcm_token'=>$this->fcm_token
            ];
    }
}
