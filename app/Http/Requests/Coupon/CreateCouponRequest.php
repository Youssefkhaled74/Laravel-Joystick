<?php

namespace App\Http\Requests\Coupon;

use Illuminate\Foundation\Http\FormRequest;

class CreateCouponRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            // 'code' => 'required|string|unique:coupons,code',
            'price' => 'required|numeric',
            // 'count' => 'required|numeric|min:1',
            'user_id' => 'array|required',
            'user_id.*' => 'exists:users,id',
            'min_price' => 'required|numeric',
            'type' => 'required|in:1,2',
            'expire_date' => 'required|date',
        ];
    }
}
