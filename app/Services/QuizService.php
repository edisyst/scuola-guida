<?php

namespace App\Services;

use App\Models\Question;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\User;
use App\Notifications\QuizConfermatoNotification;
use Illuminate\Support\Collection;
use RuntimeException;

class QuizService
{
    public function __construct(private NotificationService $notifications) {}

    public function create(array $data): Quiz
    {
        $questionIds = $data['questions'] ?? [];
        unset($data['questions']);

        $data['status'] = $data['status'] ?? Quiz::STATUS_DRAFT;

        $quiz = Quiz::create($data);

        if (!empty($questionIds)) {
            $quiz->questions()->sync($questionIds);
        }

        return $quiz;
    }

    public function createRandom(int $max = 30): Quiz
    {
        $quiz = Quiz::create([
            'title'         => 'QUIZ RANDOM NR.',
            'status'        => Quiz::STATUS_DRAFT,
            'max_questions' => $max,
        ]);

        $quiz->update([
            'title' => 'QUIZ RANDOM NR. ' . $quiz->id,
        ]);

        $ids = Question::inRandomOrder()->limit($max)->pluck('id');
        $quiz->questions()->attach($ids);

        return $quiz->refresh();
    }

    /*
    |--------------------------------------------------------------------------
    | STATE TRANSITIONS
    |--------------------------------------------------------------------------
    */

    public function publish(Quiz $quiz): Quiz
    {
        $this->assertNotLocked($quiz);

        $quiz->update(['status' => Quiz::STATUS_PUBLISHED]);

        return $quiz->refresh();
    }

    public function unpublish(Quiz $quiz): Quiz
    {
        $this->assertNotLocked($quiz);

        $quiz->update(['status' => Quiz::STATUS_DRAFT]);

        return $quiz->refresh();
    }

    public function confirm(Quiz $quiz, int $adminId): Quiz
    {
        if ($quiz->isConfirmed()) {
            return $quiz;
        }

        if ($quiz->questions()->count() === 0) {
            throw new RuntimeException('Impossibile confermare un quiz senza domande.');
        }

        $quiz->update([
            'status'       => Quiz::STATUS_CONFIRMED,
            'confirmed_at' => now(),
            'confirmed_by' => $adminId,
        ]);

        $quiz->refresh();

        $eligibleViewers = User::where('role', User::ROLE_VIEWER)
            ->where('registration_status', User::REG_APPROVED)
            ->get();

        if ($eligibleViewers->isNotEmpty()) {
            $this->notifications->send($eligibleViewers, new QuizConfermatoNotification($quiz));
        }

        return $quiz;
    }

    /**
     * Aggiorna la finestra di iscrizione (apertura/chiusura) di un quiz confermato.
     * I valori null sono ammessi e rappresentano "nessuna schedulazione".
     */
    public function updateSchedule(Quiz $quiz, ?string $openAt, ?string $closeAt): Quiz
    {
        if (!$quiz->isConfirmed()) {
            throw new RuntimeException('La schedulazione iscrizioni si applica solo a quiz confermati.');
        }

        $quiz->update([
            'enrollments_open_at'  => $openAt ?: null,
            'enrollments_close_at' => $closeAt ?: null,
        ]);

        return $quiz->refresh();
    }

    /*
    |--------------------------------------------------------------------------
    | GAMEPLAY
    |--------------------------------------------------------------------------
    */

    /**
     * Avvia una sessione di gioco creando un nuovo QuizAttempt.
     */
    public function startPlay(Quiz $quiz, int $userId, ?int $enrollmentId = null): array
    {
        $questions = $quiz->questions()->get();

        $attempt = QuizAttempt::create([
            'user_id'            => $userId,
            'quiz_id'            => $quiz->id,
            'quiz_enrollment_id' => $enrollmentId,
            'score'              => 0,
            'total_questions'    => $questions->count(),
            'answers'            => [],
        ]);

        return [
            'attempt'        => $attempt,
            'questions_json' => $questions->map(fn (Question $q) => [
                'id'      => $q->id,
                'text'    => $q->question,
                'image'   => $q->image ? asset('storage/' . $q->image) : null,
                'correct' => (int) $q->is_true,
            ])->all(),
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | MUTATIONS (bloccate se quiz confermato)
    |--------------------------------------------------------------------------
    */

    /**
     * @return array{ok: bool, error?: string, current: int}
     */
    public function addQuestion(Quiz $quiz, int $questionId): array
    {
        $this->assertNotLocked($quiz);

        if ($quiz->hasReachedLimit()) {
            return ['ok' => false, 'error' => 'Limite massimo raggiunto', 'current' => $quiz->questions()->count()];
        }

        $quiz->questions()->syncWithoutDetaching([$questionId]);

        return ['ok' => true, 'current' => $quiz->questions()->count()];
    }

    public function removeQuestion(Quiz $quiz, int $questionId): int
    {
        $this->assertNotLocked($quiz);

        $quiz->questions()->detach($questionId);

        return $quiz->questions()->count();
    }

    /**
     * @return array{ok: bool, error?: string, current: int, added: int}
     */
    public function bulkAddQuestions(Quiz $quiz, string $mode, array $ids, ?int $categoryId): array
    {
        $this->assertNotLocked($quiz);

        $candidateIds = $this->resolveIds($mode, $ids, $categoryId);

        if ($candidateIds->isEmpty()) {
            return ['ok' => false, 'error' => 'Nessuna selezione', 'current' => $quiz->questions()->count(), 'added' => 0];
        }

        $existingIds = $quiz->questions()->pluck('questions.id');
        $idsToInsert = $candidateIds->diff($existingIds);

        if ($idsToInsert->isEmpty()) {
            return [
                'ok'      => false,
                'error'   => 'Tutte le domande selezionate sono già presenti nel quiz',
                'current' => $quiz->questions()->count(),
                'added'   => 0,
            ];
        }

        $available = $quiz->max_questions - $quiz->questions()->count();

        if ($available <= 0) {
            return ['ok' => false, 'error' => 'Limite massimo raggiunto', 'current' => $quiz->questions()->count(), 'added' => 0];
        }

        $idsToInsert = $idsToInsert->take($available);

        $before = $quiz->questions()->count();
        $quiz->questions()->attach($idsToInsert);
        $after = $quiz->questions()->count();

        return ['ok' => true, 'current' => $after, 'added' => $after - $before];
    }

    public function bulkRemoveQuestions(Quiz $quiz, string $mode, array $ids, ?int $categoryId): int
    {
        $this->assertNotLocked($quiz);

        $idsToRemove = $this->resolveIds($mode, $ids, $categoryId);

        $quiz->questions()->detach($idsToRemove);

        return $quiz->questions()->count();
    }

    public function fillWithRandom(Quiz $quiz): array
    {
        $this->assertNotLocked($quiz);

        $available = $quiz->max_questions - $quiz->questions()->count();

        if ($available <= 0) {
            return ['ok' => false, 'error' => 'Limite massimo già raggiunto', 'current' => $quiz->questions()->count(), 'added' => 0];
        }

        $existingIds = $quiz->questions()->pluck('questions.id');
        $newIds = Question::whereNotIn('id', $existingIds)
            ->inRandomOrder()
            ->limit($available)
            ->pluck('id');

        if ($newIds->isEmpty()) {
            return ['ok' => false, 'error' => 'Nessuna domanda disponibile da aggiungere', 'current' => $quiz->questions()->count(), 'added' => 0];
        }

        $before = $quiz->questions()->count();
        $quiz->questions()->attach($newIds);
        $after = $quiz->questions()->count();

        return ['ok' => true, 'current' => $after, 'added' => $after - $before];
    }

    public function reorderQuestions(Quiz $quiz, array $orderedIds): void
    {
        $this->assertNotLocked($quiz);

        foreach ($orderedIds as $index => $id) {
            $quiz->questions()->updateExistingPivot($id, ['order' => $index]);
        }
    }

    private function resolveIds(string $mode, array $ids, ?int $categoryId): Collection
    {
        if ($mode === 'all') {
            $query = Question::query();

            if ($categoryId) {
                $query->where('category_id', $categoryId);
            }

            return $query->pluck('id');
        }

        return collect($ids);
    }

    private function assertNotLocked(Quiz $quiz): void
    {
        if ($quiz->isLocked()) {
            throw new RuntimeException('Il quiz è confermato e non può essere modificato.');
        }
    }
}
