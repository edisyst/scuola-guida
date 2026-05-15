<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRolePermissionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    public function rules(): array
    {
        return [
            'matrix'     => 'nullable|array',
            'matrix.*'   => 'array',
            'matrix.*.*' => 'in:1,0,true,false',
        ];
    }
}
