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
            'file' => 'required|file|mimes:xlsx,csv',
        ];
    }
}
