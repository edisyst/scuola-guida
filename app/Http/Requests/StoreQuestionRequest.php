<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreQuestionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->canCreateQuestion() ?? false;
    }

    public function rules(): array
    {
        return [
            'category_id' => 'required|exists:categories,id',
            'question'    => 'required|string',
            'is_true'     => 'boolean',
            'image'       => 'nullable|image|max:2048',
        ];
    }

    public function prepareForValidation(): void
    {
        // Un checkbox non spuntato non è presente nel payload; boolean() lo normalizza a false.
        $this->merge([
            'is_true' => $this->boolean('is_true'),
        ]);
    }
}
