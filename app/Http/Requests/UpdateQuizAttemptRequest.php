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
            'answers'    => 'required|array',
            'answers.*'  => 'in:0,1',
            'duration'   => 'nullable|integer|min:0',
        ];
    }
}
