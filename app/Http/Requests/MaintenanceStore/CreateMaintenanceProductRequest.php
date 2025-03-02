<?php

namespace App\Http\Requests\MaintenanceStore;

use Illuminate\Foundation\Http\FormRequest;

class CreateMaintenanceProductRequest extends FormRequest
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
            'price' => 'required|numeric',
            'quantity' => 'required|numeric',
            'repair_category_id' => 'required|exists:repair_categories,id',
            'tags' => 'required|array',
            'tags.*' => 'required|exists:tags,id',
            'uuid' => 'required|unique:maintenance_stores',
            'image' => 'required|file|max:2048',
        ];
    }
}
