<?php
namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        // You can implement authorization logic here if needed.
        return true;  // Allow all requests for now
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        // If the route is 'register', apply the registration validation rules
        if ($this->is('api/register')) {
            return [
                'username' => 'required|string|max:255',
                'email' => 'required|email|unique:users',
                'password' => 'required|confirmed|min:6',
                'phone' => 'required|unique:users',
                'addresses' => 'required|array',
                'addresses.*' => 'string',
            ];
        }

        // If the route is 'verify-otp', apply the OTP verification validation rules
        if ($this->is('api/verify-otp')) {
            return [
                'phone' => 'required',
                'otp' => 'required',
            ];
        }

        return [];
    }

    /**
     * Get the custom validation messages.
     *
     * @return array
     */
    // public function messages()
    // {
    //     return [
    //         'username.required' => 'Username is required.',
    //         'email.required' => 'Email is required.',
    //         'password.required' => 'Password is required.',
    //         'phone.required' => 'Phone number is required.',
    //         // Add more custom messages as needed
    //     ];
    // }
}
