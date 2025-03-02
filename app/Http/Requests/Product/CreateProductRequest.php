<?php

namespace App\Http\Requests\Product;

use Illuminate\Foundation\Http\FormRequest;

class CreateProductRequest extends FormRequest
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
            'name' => 'required|array',
            'name.ar' => 'required|string',
            'name.en' => 'required|string',
            'description' => 'required|array',
            'description.ar' => 'required|string',
            'description.en' => 'required|string',
            'small_description' => 'required|array',
            'small_description.ar' => 'required|string',
            'small_description.en' => 'required|string',
            'price' => 'required|numeric',
            'quantity' => 'required|numeric', // Total quantity for the product
            'category_id' => 'required|exists:categories,id',
            'brand_id' => 'required|exists:brands,id',
            'tags' => 'required|array',
            'tags.*' => 'required|exists:tags,id',
            'colors' => 'required|array', // Array of colors
            'colors.*.color' => 'required|string', // Color name
            'colors.*.quantity' => 'required|integer|min:0', // Quantity for each color
            'product_code' => 'required|string|max:255',
            'main_image' => 'required|file|max:2048',
            'images' => 'nullable|array|max:4',
            'images.*' => 'required|file|max:2048',
        ];
    }
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $totalProductQuantity = (int) $this->input('quantity');
            $totalColorQuantity = collect($this->input('colors'))->sum('quantity');

            if ($totalColorQuantity !== $totalProductQuantity) {
                $validator->errors()->add('colors', 'The total quantity of all colors must equal the product quantity.');
            }
        });
    }
}