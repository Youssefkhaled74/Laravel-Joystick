<?php

namespace App\Http\Requests\MaintenanceStore;

use Illuminate\Foundation\Http\FormRequest;

class UpdateMaintenanceProductRequest extends FormRequest
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
            'name' => 'nullable|array',
            'name.ar' => 'nullable|string',
            'name.en' => 'nullable|string',
            'description' => 'nullable|array',
            'description.ar' => 'nullable|string',
            'description.en' => 'nullable|string',
            'price' => 'nullable|numeric',
            'quantity' => 'nullable|numeric',
            'repair_category_id' => 'nullable|exists:repair_categories,id',
            'tags' => 'nullable|array',
            'tags.*' => 'nullable|exists:tags,id',
            'uuid' => 'nullable',
            'image' => 'nullable|file|max:2048',
        ];
    }
}
