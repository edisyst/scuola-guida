<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ReportFilterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->user()?->canEditQuiz() ?? false;
    }

    public function rules(): array
    {
        return [
            'from'    => ['required', 'date'],
            'to'      => ['required', 'date', 'after_or_equal:from'],
            'preset'  => ['nullable', 'in:current_month,last_month,current_quarter,last_quarter,current_year,custom'],
            'compare' => ['nullable', 'boolean'],
        ];
    }

    public function withValidator(\Illuminate\Validation\Validator $validator): void
    {
        $validator->after(function ($v) {
            $from = $this->date('from');
            $to   = $this->date('to');

            if (!$from || !$to) {
                return;
            }

            if ($from->diffInDays($to) > 730) {
                $v->errors()->add('to', 'Il periodo non può superare 2 anni.');
            }

            if ($from->lt(now()->subYears(5))) {
                $v->errors()->add('from', 'La data di inizio non può essere precedente a 5 anni fa.');
            }
        });
    }
}
