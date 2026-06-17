<?php

namespace App\Http\Requests;

use App\Models\Category;
use App\Models\DrivingModule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreStudyContentRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        $type = $this->input('studyable_type');

        $tableMap = [
            Category::class      => 'categories',
            DrivingModule::class => 'driving_modules',
        ];

        return [
            'studyable_type' => ['required', Rule::in([Category::class, DrivingModule::class])],
            'studyable_id'   => ['required', 'integer', Rule::exists($tableMap[$type] ?? 'categories', 'id')],
            'title'          => ['required', 'string', 'max:255'],
            'body'           => ['required', 'string'],
            'is_published'   => ['boolean'],
            'order'          => ['integer', 'min:0', 'max:9999'],
        ];
    }
}
