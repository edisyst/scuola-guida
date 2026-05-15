<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateQuestionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->canEditQuestion() ?? false;
    }

    public function rules(): array
    {
        return [
            'category_id'  => 'required|exists:categories,id',
            'question'     => 'required|string',
            'is_true'      => 'boolean',
            'image'        => 'nullable|image|max:2048',
            'remove_image' => 'boolean',
        ];
    }

    public function prepareForValidation(): void
    {
        // Un checkbox non spuntato non è presente nel payload; boolean() lo normalizza a false.
        $this->merge([
            'is_true'      => $this->boolean('is_true'),
            'remove_image' => $this->boolean('remove_image'),
        ]);
    }
}
