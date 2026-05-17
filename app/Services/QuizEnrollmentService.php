<?php

namespace App\Services;

use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\QuizEnrollment;
use App\Models\User;
use App\Notifications\IscrizioneQuizApprovataNotification;
use App\Notifications\IscrizioneQuizRiapertaNotification;
use App\Notifications\IscrizioneQuizRifiutataNotification;
use App\Notifications\NuovaIscrizioneQuizNotification;
use App\Notifications\QuizEsameCompletatoNotification;
use RuntimeException;

class QuizEnrollmentService
{
    public function __construct(private NotificationService $notifications) {}

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

        $enrollment = QuizEnrollment::create([
            'quiz_id' => $quiz->id,
            'user_id' => $user->id,
            'status'  => QuizEnrollment::STATUS_PENDING,
        ]);

        $this->notifications->sendToAdmins(new NuovaIscrizioneQuizNotification($user, $quiz));

        return $enrollment;
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

        $enrollment->refresh();
        $enrollment->loadMissing(['user', 'quiz']);

        if ($enrollment->user) {
            $this->notifications->send(
                $enrollment->user,
                new IscrizioneQuizApprovataNotification($enrollment->quiz)
            );
        }

        return $enrollment;
    }

    public function reject(QuizEnrollment $enrollment, User $admin, ?string $reason = null): QuizEnrollment
    {
        if (!$enrollment->isPending()) {
            throw new RuntimeException('Solo le iscrizioni in attesa possono essere rifiutate.');
        }

        $enrollment->update([
            'status'      => QuizEnrollment::STATUS_REJECTED,
            'reviewed_at' => now(),
            'reviewed_by' => $admin->id,
        ]);

        $enrollment->refresh();
        $enrollment->loadMissing(['user', 'quiz']);

        if ($enrollment->user) {
            $this->notifications->send(
                $enrollment->user,
                new IscrizioneQuizRifiutataNotification($enrollment->quiz, $reason)
            );
        }

        return $enrollment;
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

        $enrollment = QuizEnrollment::create([
            'quiz_id'     => $quiz->id,
            'user_id'     => $user->id,
            'status'      => QuizEnrollment::STATUS_APPROVED,
            'reviewed_at' => now(),
            'reviewed_by' => $admin->id,
        ]);

        $this->notifications->send($user, new IscrizioneQuizRiapertaNotification($quiz));

        return $enrollment;
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

        $enrollment->refresh();
        $enrollment->loadMissing(['user', 'quiz']);

        if ($enrollment->user && $enrollment->quiz) {
            $this->notifications->sendToAdmins(
                new QuizEsameCompletatoNotification($enrollment->user, $enrollment->quiz, $attempt)
            );
        }

        return $enrollment;
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
