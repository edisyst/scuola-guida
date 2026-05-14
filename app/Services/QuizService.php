<?php

namespace App\Services;

use App\Models\Question;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use Illuminate\Support\Collection;

class QuizService
{
    public function create(array $data): Quiz
    {
        $questionIds = $data['questions'] ?? [];
        unset($data['questions']);

        $quiz = Quiz::create($data);

        if (!empty($questionIds)) {
            $quiz->questions()->sync($questionIds);
        }

        return $quiz;
    }

    public function update(Quiz $quiz, array $data): Quiz
    {
        $questionIds = $data['questions'] ?? null;
        unset($data['questions']);

        $quiz->update($data);

        // null = campo assente → pivot invariato; [] = zero domande selezionate → svuota pivot.
        if ($questionIds !== null) {
            $quiz->questions()->sync($questionIds);
        }

        return $quiz;
    }

    public function createRandom(int $max = 30): Quiz
    {
        $quiz = Quiz::create([
            'title'         => 'QUIZ RANDOM NR.',
            'max_questions' => $max,
        ]);

        $quiz->update([
            'title' => 'QUIZ RANDOM NR. ' . $quiz->id,
        ]);

        $ids = Question::inRandomOrder()->limit($max)->pluck('id');
        $quiz->questions()->attach($ids);

        return $quiz->refresh();
    }

    /**
     * Avvia una sessione di gioco creando un nuovo QuizAttempt.
     */
    public function startPlay(Quiz $quiz, int $userId): array
    {
        $questions = $quiz->questions()->get();

        // Placeholder: serve l'attemptId prima che il quiz venga giocato.
        // Score e answers vengono popolati da QuizAttemptController::store() al submit.
        $attempt = QuizAttempt::create([
            'user_id'         => $userId,
            'quiz_id'         => $quiz->id,
            'score'           => 0,
            'total_questions' => $questions->count(),
            'answers'         => [],
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

    /**
     * Aggiunge una singola domanda al quiz rispettando il limite.
     *
     * @return array{ok: bool, error?: string, current: int}
     */
    public function addQuestion(Quiz $quiz, int $questionId): array
    {
        if ($quiz->hasReachedLimit()) {
            return ['ok' => false, 'error' => 'Limite massimo raggiunto', 'current' => $quiz->questions()->count()];
        }

        $quiz->questions()->syncWithoutDetaching([$questionId]);

        return ['ok' => true, 'current' => $quiz->questions()->count()];
    }

    public function removeQuestion(Quiz $quiz, int $questionId): int
    {
        $quiz->questions()->detach($questionId);

        return $quiz->questions()->count();
    }

    /**
     * Bulk add: aggiunge più domande, filtrando duplicati e rispettando il limite.
     *
     * @return array{ok: bool, error?: string, current: int, added: int}
     */
    public function bulkAddQuestions(Quiz $quiz, string $mode, array $ids, ?int $categoryId): array
    {
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

        // Tronca silenziosamente: inserisce solo fino al limite, senza errore.
        $idsToInsert = $idsToInsert->take($available);

        $before = $quiz->questions()->count();
        $quiz->questions()->attach($idsToInsert);
        $after = $quiz->questions()->count();

        return ['ok' => true, 'current' => $after, 'added' => $after - $before];
    }

    public function bulkRemoveQuestions(Quiz $quiz, string $mode, array $ids, ?int $categoryId): int
    {
        $idsToRemove = $this->resolveIds($mode, $ids, $categoryId);

        $quiz->questions()->detach($idsToRemove);

        return $quiz->questions()->count();
    }

    public function reorderQuestions(Quiz $quiz, array $orderedIds): void
    {
        foreach ($orderedIds as $index => $id) {
            $quiz->questions()->updateExistingPivot($id, ['order' => $index]);
        }
    }

    public function calculateScore(array $answers): int
    {
        $score = 0;

        foreach ($answers as $questionId => $answer) {
            $question = Question::find($questionId);

            if ($question && $question->is_true == $answer) {
                $score++;
            }
        }

        return $score;
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
}
