<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;

class ConfirmPasswordRequest extends FormRequest
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
            'password' => 'required|string|min:6',
            'confirm_password' => 'required|string|same:password',
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
    //         'password.required' => 'Password is required.',
    //         'password.min' => 'Password must be at least 6 characters.',
    //         'confirm_password.required' => 'Confirm Password is required.',
    //         'confirm_password.same' => 'Confirm Password must match the Password.',
    //     ];
    // }
}
