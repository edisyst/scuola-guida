<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDrivingModuleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->user()->canManageDrivingModules();
    }

    public function rules(): array
    {
        return [
            'license_type_id' => ['required', 'exists:license_types,id'],
            // Codice univoco per tipo di patente (unique scoped)
            'code'            => [
                'required',
                'string',
                'max:5',
                Rule::unique('driving_modules', 'code')
                    ->where('license_type_id', $this->license_type_id),
            ],
            'name'            => ['required', 'string', 'max:100'],
            'description'     => ['nullable', 'string', 'max:500'],
            'required_hours'  => ['required', 'numeric', 'min:0.5', 'max:10'],
            'sort_order'      => ['nullable', 'integer', 'min:0'],
        ];
    }
}
