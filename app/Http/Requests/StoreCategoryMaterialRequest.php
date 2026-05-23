<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCategoryMaterialRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->user()->canEditCategory();
    }

    public function rules(): array
    {
        return [
            'type'         => ['required', 'in:pdf,link,note'],
            'title'        => ['required', 'string', 'max:255'],
            'file'         => [$this->input('type') === 'pdf' ? 'required' : 'nullable', 'file', 'mimes:pdf', 'max:10240'],
            'url_or_path'  => [$this->input('type') === 'link' ? 'required' : 'nullable', 'url', 'max:1000'],
            'content'      => [$this->input('type') === 'note' ? 'required' : 'nullable', 'string'],
        ];
    }
}
