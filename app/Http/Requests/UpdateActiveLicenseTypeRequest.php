<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateActiveLicenseTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isViewer() || $this->user()->canEditUser();
    }

    public function rules(): array
    {
        return [
            'active_license_type_id' => [
                'required',
                'exists:license_types,id',
                function ($attribute, $value, $fail) {
                    $licenseType = \App\Models\LicenseType::find($value);
                    if ($licenseType && !$licenseType->is_active) {
                        $fail(__('validation.license_type_inactive'));
                    }
                },
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'active_license_type_id.required' => __('validation.license_type_required'),
            'active_license_type_id.exists'   => __('validation.license_type_invalid'),
        ];
    }
}
