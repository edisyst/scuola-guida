<?php

namespace App\Http\Requests;

use App\Models\Quiz;
use App\Services\StudyService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StartStudyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'source' => [
                'required',
                Rule::in([
                    StudyService::SOURCE_QUIZ,
                    StudyService::SOURCE_CATEGORY,
                    StudyService::SOURCE_RANDOM,
                    StudyService::SOURCE_FLAGGED,
                ]),
            ],
            'quiz_id' => [
                'required_if:source,' . StudyService::SOURCE_QUIZ,
                'nullable',
                Rule::exists('quizzes', 'id')->whereIn('status', [
                    Quiz::STATUS_PUBLISHED,
                    Quiz::STATUS_CONFIRMED,
                ]),
            ],
            'category_id' => [
                'required_if:source,' . StudyService::SOURCE_CATEGORY,
                'nullable',
                Rule::exists('categories', 'id'),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'quiz_id.required_if'     => 'Seleziona un quiz.',
            'quiz_id.exists'          => 'Il quiz selezionato non è disponibile per la modalità studio.',
            'category_id.required_if' => 'Seleziona una categoria.',
            'category_id.exists'      => 'La categoria selezionata non esiste.',
            'source.in'               => 'Sorgente non valida.',
        ];
    }

    public function sourceId(): ?int
    {
        return match ($this->input('source')) {
            StudyService::SOURCE_QUIZ     => (int) $this->input('quiz_id'),
            StudyService::SOURCE_CATEGORY => (int) $this->input('category_id'),
            default                       => null,
        };
    }
}
