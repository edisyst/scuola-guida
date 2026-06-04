<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCategoryTranslationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->user()->canEditCategory();
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
        ];
    }
}
