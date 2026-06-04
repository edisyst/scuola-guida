<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SwitchLocaleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $supported = implode(',', array_keys(config('locales.supported', [])));

        return [
            'locale' => ['required', 'string', 'in:' . $supported],
        ];
    }
}
