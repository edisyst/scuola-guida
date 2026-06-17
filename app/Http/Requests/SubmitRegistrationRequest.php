<?php

namespace App\Http\Requests;

use App\Models\User;
use App\Services\FormFieldService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SubmitRegistrationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() && $this->user()->isViewer();
    }

    public function rules(): array
    {
        $userId = $this->user()->id;
        $hasExistingDocument = !empty($this->user()->id_document_path);

        $rules = app(FormFieldService::class)->validationRules('enrollment');

        // Augment fiscal_code with unique + regex constraints if enabled
        if (isset($rules['fiscal_code'])) {
            $rules['fiscal_code'][] = 'regex:/^[A-Za-z0-9]{11,16}$/';
            $rules['fiscal_code'][] = Rule::unique(User::class, 'fiscal_code')->ignore($userId);
        }

        // id_document is required only on first submit; nullable if file already uploaded
        if (isset($rules['id_document'])) {
            $rules['id_document'][0] = $hasExistingDocument ? 'nullable' : 'required';
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'fiscal_code.regex'    => 'Il codice fiscale non è in un formato valido.',
            'fiscal_code.unique'   => 'Questo codice fiscale è già stato registrato da un altro utente.',
            'birth_date.before'    => 'La data di nascita deve essere precedente a oggi.',
            'id_document.required' => 'Il documento di identità è obbligatorio.',
            'id_document.mimes'    => 'Il documento deve essere un file PDF, JPG o PNG.',
            'id_document.max'      => 'Il documento non può superare 5 MB.',
        ];
    }

    public function attributes(): array
    {
        return [
            'first_name'  => 'nome',
            'last_name'   => 'cognome',
            'address'     => 'indirizzo',
            'birth_date'  => 'data di nascita',
            'birth_place' => 'luogo di nascita',
            'fiscal_code' => 'codice fiscale',
            'id_document' => 'documento di identità',
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->filled('fiscal_code')) {
            $this->merge([
                'fiscal_code' => strtoupper(trim($this->input('fiscal_code'))),
            ]);
        }
    }
}
