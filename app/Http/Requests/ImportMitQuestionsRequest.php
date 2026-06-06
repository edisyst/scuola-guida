<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ImportMitQuestionsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->canCreateQuestion() ?? false;
    }

    public function rules(): array
    {
        return [
            'file'             => [
                'required',
                'file',
                'mimes:xlsx,xls,csv',
                'max:' . config('mit_import.max_file_size_kb'),
            ],
            'license_type_id'  => 'required|exists:license_types,id|integer',
            'update_existing'  => 'boolean',
            'topic_filter'     => 'nullable|integer|min:1|max:25',
            'dry_run'          => 'boolean',
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
