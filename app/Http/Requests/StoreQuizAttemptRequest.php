<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreQuizAttemptRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'quiz_id'    => 'required|exists:quizzes,id',
            'answers'    => 'required|array',
            'answers.*'  => 'in:0,1',
            'duration'   => 'nullable|integer|min:0',
        ];
    }
}
