<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSystemSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->user()->isAdmin();
    }

    public function rules(): array
    {
        return [
            'school_name'          => ['nullable', 'string', 'max:150'],
            'school_tagline'       => ['nullable', 'string', 'max:255'],
            'school_address'       => ['nullable', 'string', 'max:255'],
            'school_phone'         => ['nullable', 'string', 'max:20'],
            'school_email'         => ['nullable', 'email', 'max:150'],
            'school_license_number'=> ['nullable', 'string', 'max:50'],
            'logo'              => ['nullable', 'file', 'mimes:jpg,jpeg,png,svg', 'max:2048'],
            'logo_dark'         => ['nullable', 'file', 'mimes:jpg,jpeg,png,svg', 'max:2048'],
            'carousel_images'   => ['nullable', 'array', 'max:4'],
            'carousel_images.*' => ['file', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ];
    }

}
