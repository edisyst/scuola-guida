<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ImportQuestionsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->canCreateQuestion() ?? false;
    }

    public function rules(): array
    {
        return [
            'file'             => 'required|file|mimes:xlsx,csv|max:5120',
            'license_type_id'  => 'required|exists:license_types,id|integer',
        ];
    }

    public function messages(): array
    {
        return [
            'license_type_id.required' => 'Il tipo di patente è obbligatorio.',
            'license_type_id.exists'   => 'Il tipo di patente selezionato non esiste.',
        ];
    }
}
