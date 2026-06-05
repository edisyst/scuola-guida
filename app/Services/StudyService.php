<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Question;
use App\Models\Quiz;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Session;
use RuntimeException;

class StudyService
{
    public const SOURCE_QUIZ      = 'quiz';
    public const SOURCE_CATEGORY  = 'category';
    public const SOURCE_RANDOM    = 'random';
    public const SOURCE_FLAGGED   = 'flagged';
    public const SOURCE_BOOKMARKS = 'bookmarks';

    public const KEY_QUESTIONS = 'study_questions';
    public const KEY_INDEX     = 'study_index';
    public const KEY_FLAGGED   = 'study_flagged';
    public const KEY_ANSWERS   = 'study_answers';
    public const KEY_SOURCE    = 'study_source';

    public const RANDOM_LIMIT = 30;

    /*
    |--------------------------------------------------------------------------
    | INIT
    |--------------------------------------------------------------------------
    */

    /**
     * Inizializza una nuova sessione studio raccogliendo gli ID delle domande
     * in base alla sorgente scelta.
     */
    public function start(string $source, ?int $sourceId = null): void
    {
        $ids = match ($source) {
            self::SOURCE_QUIZ       => $this->questionsFromQuiz($sourceId),
            self::SOURCE_CATEGORY   => $this->questionsFromCategory($sourceId),
            self::SOURCE_RANDOM     => $this->randomQuestions(),
            self::SOURCE_FLAGGED    => $this->flaggedFromSession(),
            self::SOURCE_BOOKMARKS  => $this->questionsFromBookmarks(),
            default                 => throw new RuntimeException("Sorgente studio non valida: {$source}"),
        };

        if (empty($ids)) {
            throw new RuntimeException('Nessuna domanda disponibile per la sorgente scelta.');
        }

        Session::put(self::KEY_QUESTIONS, array_values($ids));
        Session::put(self::KEY_INDEX, 0);
        Session::put(self::KEY_FLAGGED, []);
        Session::put(self::KEY_ANSWERS, []);
        Session::put(self::KEY_SOURCE, $source);
    }

    public function hasSession(): bool
    {
        return !empty(Session::get(self::KEY_QUESTIONS));
    }

    public function clear(): void
    {
        Session::forget([
            self::KEY_QUESTIONS,
            self::KEY_INDEX,
            self::KEY_FLAGGED,
            self::KEY_ANSWERS,
            self::KEY_SOURCE,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | NAVIGATION
    |--------------------------------------------------------------------------
    */

    public function questionIds(): array
    {
        return Session::get(self::KEY_QUESTIONS, []);
    }

    public function count(): int
    {
        return count($this->questionIds());
    }

    public function currentIndex(): int
    {
        return (int) Session::get(self::KEY_INDEX, 0);
    }

    /**
     * Imposta l'indice corrente clampato all'intervallo valido.
     */
    public function setIndex(int $index): int
    {
        $count = $this->count();

        if ($count === 0) {
            return 0;
        }

        $clamped = max(0, min($index, $count - 1));
        Session::put(self::KEY_INDEX, $clamped);

        return $clamped;
    }

    /**
     * Restituisce la domanda corrente caricata (con relazione category).
     */
    public function currentQuestion(): ?Question
    {
        $ids = $this->questionIds();

        if (empty($ids)) {
            return null;
        }

        $id = $ids[$this->currentIndex()] ?? null;

        return $id ? Question::with('category')->find($id) : null;
    }

    /*
    |--------------------------------------------------------------------------
    | FLAG
    |--------------------------------------------------------------------------
    */

    public function toggleFlag(int $questionId): bool
    {
        // Solo domande appartenenti alla sessione possono essere flaggate.
        if (!in_array($questionId, $this->questionIds(), true)) {
            throw new RuntimeException('La domanda non fa parte della sessione di studio.');
        }

        $flagged = Session::get(self::KEY_FLAGGED, []);
        $key     = array_search($questionId, $flagged, true);

        if ($key === false) {
            $flagged[] = $questionId;
            $isFlagged = true;
        } else {
            unset($flagged[$key]);
            $isFlagged = false;
        }

        Session::put(self::KEY_FLAGGED, array_values($flagged));

        return $isFlagged;
    }

    public function flaggedIds(): array
    {
        return Session::get(self::KEY_FLAGGED, []);
    }

    public function isFlagged(int $questionId): bool
    {
        return in_array($questionId, $this->flaggedIds(), true);
    }

    /*
    |--------------------------------------------------------------------------
    | ANSWERS
    |--------------------------------------------------------------------------
    */

    /**
     * Registra la risposta dell'utente per una domanda della sessione.
     * Le risposte vengono usate solo per il conteggio nel riepilogo.
     */
    public function recordAnswer(int $questionId, int $answer): void
    {
        if (!in_array($questionId, $this->questionIds(), true)) {
            return;
        }

        $answers              = Session::get(self::KEY_ANSWERS, []);
        $answers[$questionId] = $answer;
        Session::put(self::KEY_ANSWERS, $answers);
    }

    public function answers(): array
    {
        return Session::get(self::KEY_ANSWERS, []);
    }

    /*
    |--------------------------------------------------------------------------
    | SUMMARY
    |--------------------------------------------------------------------------
    */

    /**
     * @return array{
     *     total: int,
     *     answered: int,
     *     flagged_count: int,
     *     flagged: \Illuminate\Database\Eloquent\Collection<int, Question>,
     *     source: string|null
     * }
     */
    public function summary(): array
    {
        $flaggedIds = $this->flaggedIds();

        $flagged = empty($flaggedIds)
            ? new Collection()
            : Question::with('category.translations')->whereIn('id', $flaggedIds)->get();

        return [
            'total'         => $this->count(),
            'answered'      => count($this->answers()),
            'flagged_count' => count($flaggedIds),
            'flagged'       => $flagged,
            'source'        => Session::get(self::KEY_SOURCE),
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | INTERNALS — sorgenti domande
    |--------------------------------------------------------------------------
    */

    private function questionsFromQuiz(?int $quizId): array
    {
        if (!$quizId) {
            throw new RuntimeException('Quiz non specificato.');
        }

        $quiz = Quiz::query()
            ->whereIn('status', [Quiz::STATUS_PUBLISHED, Quiz::STATUS_CONFIRMED])
            ->find($quizId);

        if (!$quiz) {
            throw new RuntimeException('Quiz non disponibile per la modalità studio.');
        }

        return $quiz->questions()->pluck('questions.id')->all();
    }

    private function questionsFromCategory(?int $categoryId): array
    {
        if (!$categoryId) {
            throw new RuntimeException('Categoria non specificata.');
        }

        $category = Category::find($categoryId);

        if (!$category) {
            throw new RuntimeException('Categoria non trovata.');
        }

        $licenseType = auth()->user()->getActiveLicenseType();
        $query       = $category->questions();

        if ($licenseType) {
            $query->whereHas('category', fn($q) =>
                $q->whereHas('licenseTypes', fn($lq) => $lq->where('license_types.id', $licenseType->id))
            );
        }

        return $query->inRandomOrder()->pluck('id')->all();
    }

    private function randomQuestions(): array
    {
        $licenseType = auth()->user()->getActiveLicenseType();

        $query = Question::query();

        if ($licenseType) {
            $query->whereHas('category', fn($q) =>
                $q->whereHas('licenseTypes', fn($lq) => $lq->where('license_types.id', $licenseType->id))
            );
        }

        return $query->inRandomOrder()
            ->limit(self::RANDOM_LIMIT)
            ->pluck('id')
            ->all();
    }

    private function flaggedFromSession(): array
    {
        $flagged = $this->flaggedIds();

        if (empty($flagged)) {
            throw new RuntimeException('Nessuna domanda marcata da ripassare.');
        }

        return $flagged;
    }

    private function questionsFromBookmarks(): array
    {
        return auth()->user()
            ->bookmarkedQuestions()
            ->pluck('questions.id')
            ->all();
    }
}
