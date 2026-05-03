<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreQuizRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'max_questions' => 'required|integer|min:1|max:100',
            'questions'     => 'nullable|array',
            'questions.*'   => 'exists:questions,id',
        ];
    }

    public function withValidator($validator)
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
