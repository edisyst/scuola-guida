<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateDrivingModuleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->user()->canManageDrivingModules();
    }

    public function rules(): array
    {
        /** @var \App\Models\DrivingModule $module */
        $module = $this->route('driving_module');

        return [
            // license_type_id è immutabile: non incluso nelle regole
            'code'           => [
                'required',
                'string',
                'max:5',
                Rule::unique('driving_modules', 'code')
                    ->where('license_type_id', $module->license_type_id)
                    ->ignore($module),
            ],
            'name'           => ['required', 'string', 'max:100'],
            'description'    => ['nullable', 'string', 'max:500'],
            'required_hours' => ['required', 'numeric', 'min:0.5', 'max:10'],
            'sort_order'     => ['nullable', 'integer', 'min:0'],
        ];
    }
}
