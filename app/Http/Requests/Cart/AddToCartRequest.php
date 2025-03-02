<?php

namespace App\Http\Requests\Cart;

use Illuminate\Foundation\Http\FormRequest;

class AddToCartRequest extends FormRequest
{
    public function authorize()
    {
        return true; // Allow all users to make this request
    }

    public function rules()
    {
        return [
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer|min:1',
            'color' => 'required|string|exists:product_colors,color,product_id,' . $this->product_id,
        ];
    }

    // public function messages()
    // {
    //     return [
    //         'product_id.exists' => 'The product you are trying to add does not exist.',
    //         'quantity.min' => 'Quantity must be at least 1.',
    //     ];
    // }
}
