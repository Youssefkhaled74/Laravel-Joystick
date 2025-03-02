<?php

namespace App\Http\Requests\Area;

use Illuminate\Foundation\Http\FormRequest;

class CreateAreaRequest extends FormRequest
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
            // 'name' => 'required|string|unique:areas,name',
            'name' => 'required|array',
            'name.ar' => 'required|string|max:255|unique:areas,name->ar',
            'name.en' => 'required|string|max:255|unique:areas,name->en',
            'price' => 'required|numeric',
            'is_active' => 'nullable|boolean',
        ];
    }
}
