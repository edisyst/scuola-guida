<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreDrivingSessionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->user()->canRegisterDrivingSession();
    }

    public function rules(): array
    {
        return [
            'driving_module_id' => ['required', 'exists:driving_modules,id'],
            'conducted_at'      => ['required', 'date', 'before_or_equal:today'],
            'duration_minutes'  => ['required', 'integer', 'min:15', 'max:120'],
            'notes'             => ['nullable', 'string', 'max:1000'],
        ];
    }
}
