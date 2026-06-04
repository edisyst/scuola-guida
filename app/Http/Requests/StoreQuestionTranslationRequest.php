<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreQuestionTranslationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->canEditQuestion() ?? false;
    }

    public function rules(): array
    {
        return [
            'locale' => [
                'required',
                'string',
                Rule::in(array_keys(config('locales.exam', []))),
            ],
            'text' => 'required|string|max:1000',
        ];
    }
}
