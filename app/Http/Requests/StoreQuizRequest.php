<?php

namespace App\Http\Requests;

use App\Models\Quiz;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
            'status'        => ['nullable', Rule::in([Quiz::STATUS_DRAFT, Quiz::STATUS_PUBLISHED])],
            'questions'     => 'nullable|array',
            'questions.*'   => 'exists:questions,id',
        ];
    }

    public function prepareForValidation(): void
    {
        $status = $this->input('status', Quiz::STATUS_DRAFT);

        // Solo admin può creare direttamente in stato published
        if ($status === Quiz::STATUS_PUBLISHED && !$this->user()?->isAdmin()) {
            $status = Quiz::STATUS_DRAFT;
        }

        $this->merge(['status' => $status]);
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
