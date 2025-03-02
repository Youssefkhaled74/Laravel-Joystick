<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePasswordRequest extends FormRequest
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
            'old_password' => 'required|string|min:6',
            'new_password' => 'required|string|min:6|confirmed',
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
    //         'old_password.required' => 'Old password is required.',
    //         'old_password.min' => 'Old password must be at least 6 characters.',
    //         'new_password.required' => 'New password is required.',
    //         'new_password.min' => 'New password must be at least 6 characters.',
    //         'new_password.confirmed' => 'New password confirmation does not match.',
    //     ];
    // }
}
