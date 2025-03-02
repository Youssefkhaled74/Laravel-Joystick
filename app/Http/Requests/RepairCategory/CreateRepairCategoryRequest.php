<?php

namespace App\Http\Requests\RepairCategory;

use Illuminate\Foundation\Http\FormRequest;

class CreateRepairCategoryRequest extends FormRequest
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
            'name.ar' => 'required|string|max:255',
            'name.en' => 'required|string|max:255',
            'tags' => 'required|array',
            'tags.*' => 'required|exists:tags,id',
            'image' => 'required|file|max:2048',
            // 'parent_id' => 'required'
        ];
    }
}
