<?php

namespace App\Http\Requests\Cart;

use Illuminate\Foundation\Http\FormRequest;

class RemoveFromCartRequest extends FormRequest
{
    public function authorize()
    {
        return true; // Allow all users to make this request
    }

    public function rules()
    {
        return [
            'product_id' => 'required|exists:products,id',
        ];
    }

    // public function messages()
    // {
    //     return [
    //         'product_id.exists' => 'The product you are trying to remove does not exist.',
    //     ];
    // }
}
