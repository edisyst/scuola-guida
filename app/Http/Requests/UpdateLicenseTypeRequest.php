<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateLicenseTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->user()->canEditLicenseType();
    }

    public function rules(): array
    {
        $licenseType = $this->route('license_type');

        return [
            'code' => ['required', 'string', 'max:10', Rule::unique('license_types', 'code')->ignore($licenseType->id)],
            'name' => 'required|string|max:100',
            'description' => 'nullable|string|max:500',
            'exam_questions' => 'nullable|integer|min:1',
            'exam_minutes' => 'nullable|integer|min:1',
            'exam_max_errors' => 'nullable|integer|min:1',
            'sort_order' => 'nullable|integer|min:0',
            'is_active' => 'boolean',
            'category_ids' => 'nullable|array',
            'category_ids.*' => 'exists:categories,id',
        ];
    }
}
