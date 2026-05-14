<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BulkQuizQuestionsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->canBulkQuiz() ?? false;
    }

    public function rules(): array
    {
        return [
            'mode'        => 'nullable|in:all,selection',
            'ids'         => 'nullable|array',
            'ids.*'       => 'integer|exists:questions,id',
            'category_id' => 'nullable|exists:categories,id',
        ];
    }
}
