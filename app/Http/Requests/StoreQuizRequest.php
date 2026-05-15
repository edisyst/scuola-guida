<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreQuizRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->canCreateQuiz() ?? false;
    }

    public function rules(): array
    {
        return [
            'title'         => 'required|string|max:255',
            'max_questions' => 'required|integer|min:1|max:100',
            'time_limit'    => 'nullable|integer|min:0',
            'max_errors'    => 'nullable|integer|min:0',
            'is_active'     => 'boolean',
            'questions'     => 'nullable|array',
            'questions.*'   => 'exists:questions,id',
        ];
    }

    public function prepareForValidation(): void
    {
        // Un checkbox non spuntato non è presente nel payload; boolean() lo normalizza a false.
        $this->merge([
            'is_active' => $this->boolean('is_active'),
        ]);
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {

            $questions = $this->input('questions', []);
            $max = (int) $this->input('max_questions');

            if (count($questions) > $max) {
                $validator->errors()->add(
                    'questions',
                    "Hai selezionato troppe domande (" . count($questions) . "). Max consentito: $max"
                );
            }
        });
    }
}
