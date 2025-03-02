<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    public function authorize()
    {
        return true; // Allow all users to make this request
    }
    public function rules()
    {
        return [
            'phone' => 'required|string',
            'password' => 'required|string',
            'fcm_token' => 'string|nullable',
        ];
    }

    // public function messages()
    // {
    //     return [
    //         'phone.required' => 'phone is required.',
    //         'password.required' => 'Password is required.',
    //         'password.min' => 'Password must be at least 6 characters long.',
    //     ];
    // }
}
