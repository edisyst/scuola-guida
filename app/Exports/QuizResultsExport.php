<?php

namespace App\Exports;

use App\Models\Quiz;
use App\Models\QuizEnrollment;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class QuizResultsExport implements FromQuery, WithHeadings, WithMapping, WithStyles
{
    public function __construct(private Quiz $quiz) {}

    public function query()
    {
        return QuizEnrollment::query()
            ->where('quiz_id', $this->quiz->id)
            ->whereIn('status', [
                QuizEnrollment::STATUS_APPROVED,
                QuizEnrollment::STATUS_COMPLETED,
            ])
            ->with(['user', 'quizAttempt'])
            ->join('users', 'users.id', '=', 'quiz_enrollments.user_id')
            ->orderByRaw("COALESCE(NULLIF(users.last_name, ''), users.name) ASC")
            ->orderBy('users.first_name')
            ->select('quiz_enrollments.*');
    }

    public function headings(): array
    {
        return [
            'Cognome',
            'Nome',
            'Email',
            'Data tentativo',
            'Punteggio',
            'Totale domande',
            'Percentuale',
            'Esito',
            'Durata (min)',
        ];
    }

    /**
     * @param  QuizEnrollment  $enrollment
     */
    public function map($enrollment): array
    {
        $user    = $enrollment->user;
        $attempt = $enrollment->quizAttempt;

        $lastName  = $user->last_name ?: $user->name;
        $firstName = $user->first_name ?: '';
        $email     = $user->email ?: '';

        if (!$attempt) {
            return [
                $lastName,
                $firstName,
                $email,
                '',
                '',
                '',
                '',
                'Non svolto',
                '',
            ];
        }

        $total      = (int) $attempt->total_questions;
        $errors     = $total - (int) $attempt->score;
        $passed     = $total > 0 && $errors <= ($this->quiz->max_errors ?? 0);
        $percentage = $total > 0
            ? number_format(($attempt->score / $total) * 100, 1, ',', '') . '%'
            : '0,0%';

        $durationMinutes = $attempt->duration !== null
            ? number_format($attempt->duration / 60, 1, ',', '')
            : '';

        return [
            $lastName,
            $firstName,
            $email,
            $attempt->created_at?->format('d/m/Y H:i') ?? '',
            (int) $attempt->score,
            $total,
            $percentage,
            $passed ? 'Promosso' : 'Rimandato',
            $durationMinutes,
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
