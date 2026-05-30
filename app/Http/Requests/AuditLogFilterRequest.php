<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AuditLogFilterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->isAdmin();
    }

    public function rules(): array
    {
        return [
            'user_id'        => ['nullable', 'integer', 'exists:users,id'],
            'auditable_type' => ['nullable', 'string'],
            'event'          => ['nullable', 'string', 'in:created,updated,deleted'],
            'from'           => ['nullable', 'date'],
            'to'             => ['nullable', 'date', 'after_or_equal:from'],
            'search'         => ['nullable', 'string', 'max:255'],
        ];
    }
}
