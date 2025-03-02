<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true; // Allow all users to make this request
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'username' => 'required|string|max:255',
            'password' => 'required|min:8|unique:users|confirmed',
            'phone' => 'required|unique:users|numeric|digits:11',
            'apartment_number'=> 'nullable',
            'building_number'=> 'nullable',
            'floor_number'=> 'nullable',
            'latitude'=> 'nullable|numeric|between:-90,90',
            'longitude'=> 'nullable|numeric|between:-180,180',
            'addresses' => 'string|nullable',
            'fcm_token' => 'string|nullable',
        ];
    }

    /**
     * Customize the error messages.
     *
     * @return array
     */
    public function messages()
    {
        return [
            'username.required' => 'username is required',
            'password.required' => 'Password is required',
            'phone.required' => 'Phone number is required',
            'phone.unique' => 'Phone number is already taken',
            'addresses.required' => 'Address is required',
        ];
    }
}

