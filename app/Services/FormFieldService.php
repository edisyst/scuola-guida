<?php

namespace App\Services;

class FormFieldService
{
    public function getRegistrationFields(): array
    {
        $all = json_decode(setting('forms.registration_fields', '[]'), true) ?: [];
        return array_values(array_filter($all, fn($f) => $f['enabled']));
    }

    public function getEnrollmentFields(): array
    {
        $all = json_decode(setting('forms.enrollment_fields', '[]'), true) ?: [];
        return array_values(array_filter($all, fn($f) => $f['enabled']));
    }

    public function validationRules(string $flow): array
    {
        $fields = $flow === 'registration'
            ? $this->getRegistrationFields()
            : $this->getEnrollmentFields();

        $rules = [];
        foreach ($fields as $field) {
            $rules[$field['key']] = $this->buildRules($field);
        }
        return $rules;
    }

    private function buildRules(array $field): array
    {
        $req = $field['required'] ? 'required' : 'nullable';

        return match ($field['type']) {
            'date'  => [$req, 'date', 'before:today'],
            'file'  => [$req, 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
            'email' => [$req, 'string', 'email', 'max:255'],
            'tel'   => [$req, 'string', 'max:20'],
            default => [$req, 'string', 'max:255'],
        };
    }
}
