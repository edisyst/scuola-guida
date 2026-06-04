<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAccessibilityPreferencesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tts_enabled'  => ['nullable', 'boolean'],
            'tts_autoplay' => ['nullable', 'boolean'],
        ];
    }
}
