<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BulkDeleteQuestionsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->canBulkQuestion() ?? false;
    }

    public function rules(): array
    {
        return [
            'ids'   => 'required|array|min:1',
            'ids.*' => 'integer|exists:questions,id',
        ];
    }
}
