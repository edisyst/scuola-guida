<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateQuizRequest extends FormRequest
{
     //Determine if the user is authorized to make this request.
    public function authorize(): bool
    {
        return true;
    }

    // Get the validation rules that apply to the request.
    public function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'max_questions' => 'required|integer|min:1|max:100',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {

            $quiz = $this->route('quiz');

            $questions = $this->input('questions', []);
            $max = (int) $this->input('max_questions');

            // 🔥 1. controllo selezione attuale
            if (count($questions) > $max) {
                $validator->errors()->add(
                    'questions',
                    "Hai selezionato troppe domande (" . count($questions) . "). Max: $max"
                );
            }

            // 🔥 2. controllo domande già salvate (solo update)
            if ($quiz) {
                $currentCount = $quiz->questions()->count();

                if ($max < $currentCount) {
                    $validator->errors()->add(
                        'max_questions',
                        "Il limite non può essere inferiore alle domande già presenti ($currentCount)"
                    );
                }
            }
        });
    }
}
