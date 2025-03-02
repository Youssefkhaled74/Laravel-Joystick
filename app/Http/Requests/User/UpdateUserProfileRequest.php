<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;

class UpdateUserProfileRequest extends FormRequest
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
            'username' => 'nullable|string|max:255',
            'profile_picture' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
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
    //         'username.max' => 'Username cannot exceed 255 characters.',
    //         'profile_picture.image' => 'The profile picture must be an image.',
    //         'profile_picture.mimes' => 'The profile picture must be a jpeg, png, jpg, or gif.',
    //         'profile_picture.max' => 'The profile picture size must not exceed 2MB.',
    //     ];
    // }
}
