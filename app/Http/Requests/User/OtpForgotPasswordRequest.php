<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;

class OtpForgotPasswordRequest extends FormRequest
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
            'phone' => 'required|string|exists:users,phone',
        ];
    }

    /**
     * Customize the error messages.
     *
     * @return array
     */
    // public function messages()
    // {
    //     return [
    //         'phone.required' => 'Phone number is required.',
    //     ];
    // }
}
