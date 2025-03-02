<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;

class UpdateUserEmailRequest extends FormRequest
{
    public function authorize()
    {
        return true; // Allow all users to make this request
    }

    public function rules()
    {
        return [
            'email' => 'required|email|unique:users,email|max:255',
        ];
    }

    // public function messages()
    // {
    //     return [
    //         'email.email' => 'Please provide a valid email address.',
    //     ];
    // }
}
