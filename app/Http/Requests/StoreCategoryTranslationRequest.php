<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCategoryTranslationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->user()->canEditCategory();
    }

    public function rules(): array
    {
        // 'it' è sempre la fonte di verità per i contenuti, indipendente da APP_LOCALE.
        $translatable = array_diff(array_keys(config('locales.exam', [])), ['it']);

        return [
            'locale' => ['required', 'string', Rule::in($translatable)],
            'name'   => ['required', 'string', 'max:255'],
        ];
    }
}
