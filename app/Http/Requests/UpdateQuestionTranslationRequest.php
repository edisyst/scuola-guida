<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateQuestionTranslationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->canEditQuestion() ?? false;
    }

    public function rules(): array
    {
        // Il locale è nel path (route param), non nel payload.
        return [
            'text' => 'required|string|max:1000',
        ];
    }
}
