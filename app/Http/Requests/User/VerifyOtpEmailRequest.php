<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;

class VerifyOtpEmailRequest extends FormRequest
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
            'email' => 'required|email',
            'otp' => 'required|numeric',
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
    //         'email.required' => 'Email is required.',
    //         'email.email' => 'Please provide a valid email address.',
    //         'otp.required' => 'OTP is required.',
    //         'otp.numeric' => 'OTP must be numeric.',
    //     ];
    // }
}
