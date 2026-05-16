<?php

namespace App\Services;

use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\QuizEnrollment;
use App\Models\User;
use RuntimeException;

class QuizEnrollmentService
{
    /**
     * Un viewer richiede l'iscrizione a un quiz confermato.
     */
    public function request(Quiz $quiz, User $user): QuizEnrollment
    {
        if (!$quiz->isConfirmed()) {
            throw new RuntimeException('Puoi iscriverti solo a quiz confermati.');
        }

        if ($user->isViewer() && !$user->canEnrollOfficialExams()) {
            throw new RuntimeException(
                'Devi completare l\'iscrizione anagrafica ed essere approvato dall\'amministratore '
                . 'prima di poterti iscrivere agli esami ufficiali. Vai al tuo profilo per inviare i dati.'
            );
        }

        if ($this->hasActiveEnrollment($quiz, $user)) {
            throw new RuntimeException('Hai già un\'iscrizione attiva per questo quiz.');
        }

        if ($this->hasCompletedEnrollment($quiz, $user)) {
            throw new RuntimeException('Hai già svolto questo quiz. Chiedi all\'amministratore di riaprire una nuova iscrizione.');
        }

        return QuizEnrollment::create([
            'quiz_id' => $quiz->id,
            'user_id' => $user->id,
            'status'  => QuizEnrollment::STATUS_PENDING,
        ]);
    }

    public function approve(QuizEnrollment $enrollment, User $admin): QuizEnrollment
    {
        if (!$enrollment->isPending()) {
            throw new RuntimeException('Solo le iscrizioni in attesa possono essere approvate.');
        }

        $enrollment->update([
            'status'      => QuizEnrollment::STATUS_APPROVED,
            'reviewed_at' => now(),
            'reviewed_by' => $admin->id,
        ]);

        return $enrollment->refresh();
    }

    public function reject(QuizEnrollment $enrollment, User $admin): QuizEnrollment
    {
        if (!$enrollment->isPending()) {
            throw new RuntimeException('Solo le iscrizioni in attesa possono essere rifiutate.');
        }

        $enrollment->update([
            'status'      => QuizEnrollment::STATUS_REJECTED,
            'reviewed_at' => now(),
            'reviewed_by' => $admin->id,
        ]);

        return $enrollment->refresh();
    }

    /**
     * L'admin riapre una nuova iscrizione approvata per un viewer
     * che ha già svolto il quiz (o per il quale era stata rifiutata).
     */
    public function reopen(Quiz $quiz, User $user, User $admin): QuizEnrollment
    {
        if (!$quiz->isConfirmed()) {
            throw new RuntimeException('Si può riaprire l\'iscrizione solo per quiz confermati.');
        }

        if ($this->hasActiveEnrollment($quiz, $user)) {
            throw new RuntimeException('L\'utente ha già un\'iscrizione attiva.');
        }

        return QuizEnrollment::create([
            'quiz_id'     => $quiz->id,
            'user_id'     => $user->id,
            'status'      => QuizEnrollment::STATUS_APPROVED,
            'reviewed_at' => now(),
            'reviewed_by' => $admin->id,
        ]);
    }

    /**
     * Marca un'iscrizione come completata, agganciando il tentativo svolto.
     */
    public function markCompleted(QuizEnrollment $enrollment, QuizAttempt $attempt): QuizEnrollment
    {
        $enrollment->update([
            'status'       => QuizEnrollment::STATUS_COMPLETED,
            'completed_at' => now(),
        ]);

        if ($attempt->quiz_enrollment_id !== $enrollment->id) {
            $attempt->update(['quiz_enrollment_id' => $enrollment->id]);
        }

        return $enrollment->refresh();
    }

    /**
     * Restituisce l'iscrizione attiva del viewer su un quiz, se esiste.
     */
    public function activeFor(Quiz $quiz, User $user): ?QuizEnrollment
    {
        return QuizEnrollment::where('quiz_id', $quiz->id)
            ->where('user_id', $user->id)
            ->active()
            ->latest('id')
            ->first();
    }

    private function hasActiveEnrollment(Quiz $quiz, User $user): bool
    {
        return QuizEnrollment::where('quiz_id', $quiz->id)
            ->where('user_id', $user->id)
            ->active()
            ->exists();
    }

    private function hasCompletedEnrollment(Quiz $quiz, User $user): bool
    {
        return QuizEnrollment::where('quiz_id', $quiz->id)
            ->where('user_id', $user->id)
            ->completed()
            ->exists();
    }
}
