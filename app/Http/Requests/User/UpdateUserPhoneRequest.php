<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;

class UpdateUserPhoneRequest extends FormRequest
{
    public function authorize()
    {
        return true; // Allow all users to make this request
    }

    public function rules()
    {
        return [
            'phone' => 'required',
        ];
    }

    // public function messages()
    // {
    //     return [
    //         'phone.exists' => 'The phone number must be associated with an existing user.',
    //     ];
    // }
}
