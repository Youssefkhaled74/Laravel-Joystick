<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;

class AddAddressRequest extends FormRequest
{
    public function authorize()
    {
        return true; // Allow all users to make this request
    }

    public function rules()
    {
        return [
            'address' => 'required|string',
            'apartment_number' => 'required',
            'building_number' => 'required',
            'floor_number' => 'required',
            'key' => 'required',
            'area_id' => 'nullable',
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
        ];
    }

    // public function messages()
    // {
    //     return [
    //         'address.required' => 'Address is required.',
    //         'address.string' => 'Address must be a valid string.',
    //     ];
    // }
}
