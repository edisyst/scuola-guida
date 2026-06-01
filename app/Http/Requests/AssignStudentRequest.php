<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AssignStudentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->canEditUser();
    }

    public function rules(): array
    {
        return [
            'student_ids'   => ['required', 'array'],
            'student_ids.*' => ['integer', 'exists:users,id'],
        ];
    }
}
