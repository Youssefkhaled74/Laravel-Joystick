<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;

class SendOtpRequest extends FormRequest
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
            'email' => 'nullable|email|exists:users,email',
            'phone' => 'nullable|exists:users,phone',
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
    //         'email.exists' => 'The email address does not exist in our records.',
    //         'phone.exists' => 'The phone number does not exist in our records.',
    //     ];
    // }
}
