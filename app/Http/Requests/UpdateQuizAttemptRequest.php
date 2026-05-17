<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateQuizAttemptRequest extends FormRequest
{
    public function authorize(): bool
    {
        $attempt = $this->route('attempt');

        // Impedisce di modificare il tentativo di un altro utente via URL diretto.
        return $attempt && $this->user() && $attempt->user_id === $this->user()->id;
    }

    public function rules(): array
    {
        return [
            'answers'                       => 'nullable|array',
            'answers.*.correct'             => 'sometimes|integer|in:0,1',
            'answers.*.answered_at'         => 'sometimes|nullable|integer',
            'answers.*.time_spent_seconds'  => 'sometimes|nullable|integer|min:0',
            'answers.*.position'            => 'sometimes|nullable|integer|min:1',
            'duration'                      => 'nullable|integer|min:0',
        ];
    }
}
