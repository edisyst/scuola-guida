<?php

namespace App\Http\Requests;

use App\Models\User;
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

        return [
            'first_name'  => ['required', 'string', 'max:100'],
            'last_name'   => ['required', 'string', 'max:100'],
            'address'     => ['required', 'string', 'max:255'],
            'birth_date'  => ['required', 'date', 'before:today'],
            'birth_place' => ['required', 'string', 'max:150'],
            'fiscal_code' => [
                'required',
                'string',
                'regex:/^[A-Za-z0-9]{11,16}$/',
                Rule::unique(User::class, 'fiscal_code')->ignore($userId),
            ],
            // Il documento è obbligatorio al primo invio. Se l'utente sta
            // ri-inviando dati già caricati può lasciare il campo vuoto e
            // tenere il file esistente.
            'id_document' => [
                $hasExistingDocument ? 'nullable' : 'required',
                'file',
                'mimes:pdf,jpg,jpeg,png',
                'max:5120',
            ],
        ];
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
