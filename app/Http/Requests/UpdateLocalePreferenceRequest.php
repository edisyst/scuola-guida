<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateLocalePreferenceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            // null = usa la lingua di default dell'applicazione (italiano).
            'locale' => [
                'nullable',
                Rule::in(array_keys(config('locales.exam', []))),
            ],
        ];
    }

    public function prepareForValidation(): void
    {
        // Stringa vuota dalla select => null (default applicazione).
        if ($this->input('locale') === '') {
            $this->merge(['locale' => null]);
        }
    }
}
