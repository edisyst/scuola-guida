<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateQuizScheduleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    public function rules(): array
    {
        return [
            'enrollments_open_at'  => ['nullable', 'date'],
            'enrollments_close_at' => ['nullable', 'date', 'after:enrollments_open_at'],
        ];
    }

    public function messages(): array
    {
        return [
            'enrollments_close_at.after' => 'La data di chiusura deve essere successiva alla data di apertura.',
        ];
    }
}
