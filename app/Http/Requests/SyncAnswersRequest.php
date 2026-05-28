<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SyncAnswersRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'answers'              => ['required', 'array', 'min:1', 'max:500'],
            'answers.*.id'         => ['required', 'integer'],
            'answers.*.question_id'=> ['required', 'integer', 'exists:questions,id'],
            'answers.*.user_answer'=> ['required', 'in:0,1'],
            'answers.*.is_correct' => ['required', 'boolean'],
            'answers.*.answered_at'=> ['required', 'date'],
        ];
    }

    public function messages(): array
    {
        return [
            'answers.required'              => 'Il campo answers è obbligatorio.',
            'answers.*.question_id.exists'  => 'Una o più domande non esistono.',
        ];
    }
}
