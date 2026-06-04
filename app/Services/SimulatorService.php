<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Question;
use App\Models\QuizAttempt;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class SimulatorService
{
    private const SESSION_QUESTIONS = 'simulator_questions';
    private const SESSION_ATTEMPT   = 'simulator_attempt_id';

    /**
     * Costruisce la lista di domande per una sessione simulatore
     * secondo la distribuzione configurata in config/simulator.php.
     *
     * Pre-carica tutte le categorie in memoria con una singola query ed esegue
     * il lookup per nome in PHP, evitando N query Category nel ciclo.
     */
    public function buildQuestionList(): Collection
    {
        $distribution = config('simulator.distribution', []);
        $target       = (int) config('simulator.questions', 30);
        $questions    = collect();

        // 1 query invece di N: recupera tutte le categorie e filtra in memoria.
        $allCategories = Category::select('id', 'name')->get();

        foreach ($distribution as $categoryName => $count) {
            $needle   = strtolower($categoryName);
            $category = $allCategories->first(
                fn ($c) => str_contains(strtolower($c->name), $needle)
            );

            if (!$category) {
                Log::warning("SimulatorService: categoria non trovata nel DB: {$categoryName}");
                continue;
            }

            $existingIds = $questions->pluck('id');
            $extracted   = Question::where('category_id', $category->id)
                ->whereNotIn('id', $existingIds)
                ->inRandomOrder()
                ->limit($count)
                ->get();

            if ($extracted->count() < $count) {
                Log::warning(
                    "SimulatorService: categoria '{$categoryName}' ha solo "
                    . "{$extracted->count()} domande estraibili, richieste {$count}."
                );
            }

            $questions = $questions->merge($extracted);
        }

        // Integrazione finale per raggiungere il target di domande configurato.
        if ($questions->count() < $target) {
            $existingIds = $questions->pluck('id');
            $extra       = Question::whereNotIn('id', $existingIds)
                ->inRandomOrder()
                ->limit($target - $questions->count())
                ->get();

            $questions = $questions->merge($extra);

            Log::warning(
                "SimulatorService: integrazione con {$extra->count()} domande extra "
                . "per raggiungere il target di {$target}."
            );
        }

        // Tronca al target esatto e mescola.
        return $questions->take($target)->shuffle()->values();
    }

    /**
     * Carica le domande della sessione corrente preservando l'ordine memorizzato.
     */
    public function loadSessionQuestions(): Collection
    {
        $ids = session(self::SESSION_QUESTIONS, []);

        if (empty($ids)) {
            return collect();
        }

        // Eager-load translations (Feature 7.1): localizzazione testo senza N+1.
        return Question::with('translations')
            ->whereIn('id', $ids)
            ->get()
            ->sortBy(fn ($q) => array_search($q->id, $ids))
            ->values();
    }

    /**
     * Inizializza la sessione del simulatore creando un QuizAttempt con quiz_id = null.
     */
    public function startSession(int $userId, Collection $questions): QuizAttempt
    {
        $attempt = QuizAttempt::create([
            'user_id'         => $userId,
            'quiz_id'         => null,
            'total_questions' => $questions->count(),
            'score'           => 0,
            'duration'        => null,
            'answers'         => [],
        ]);

        session([
            self::SESSION_QUESTIONS => $questions->pluck('id')->all(),
            self::SESSION_ATTEMPT   => $attempt->id,
        ]);

        return $attempt;
    }

    public function clearSession(): void
    {
        session()->forget([self::SESSION_QUESTIONS, self::SESSION_ATTEMPT]);
    }

    public function currentAttemptId(): ?int
    {
        return session(self::SESSION_ATTEMPT);
    }

    public function hasActiveSession(): bool
    {
        return $this->currentAttemptId() !== null
            && !empty(session(self::SESSION_QUESTIONS, []));
    }

    /**
     * Aggiorna un tentativo simulatore con le risposte correnti, ricalcolando lo score
     * direttamente dalle domande in sessione (quiz_id è null, quindi non si può passare dal Quiz).
     */
    public function updateAttempt(QuizAttempt $attempt, array $answers, ?int $duration): QuizAttempt
    {
        $questionIds = session(self::SESSION_QUESTIONS, []);
        $correctMap  = Question::whereIn('id', $questionIds)
            ->pluck('is_true', 'id');

        $normalized = $this->normalizeAnswers($answers);
        $score      = $this->scoreAnswers($normalized, $correctMap);

        $attempt->update([
            'answers'         => $normalized,
            'score'           => $score,
            'total_questions' => count($questionIds),
            'duration'        => $duration ?? $attempt->duration,
        ]);

        return $attempt;
    }

    /**
     * Costruisce il payload di dettaglio risultato per la view simulator.result.
     * Non passa dal QuizAttemptService perché quel service dipende da $attempt->quiz->questions
     * (e per il simulatore quiz_id è null). Le domande vengono ricostruite dagli ID
     * presenti nella mappa `answers` del tentativo.
     */
    public function getResultDetail(QuizAttempt $attempt): array
    {
        $attempt->loadMissing('user');

        $answeredQids    = array_map('intval', array_keys($attempt->answers ?? []));
        $totalConfigured = (int) ($attempt->total_questions ?: config('simulator.questions'));

        // Carica tutte le domande risposte. Eager-load translations (Feature 7.1)
        // per localizzare il testo nella lingua preferita dell'utente senza N+1.
        $locale        = $attempt->user?->getPreferredLocale() ?? config('locales.default', 'it');
        $questionsById = Question::with(['category.translations', 'translations'])
            ->whereIn('id', $answeredQids)->get()->keyBy('id');

        $rows = collect($answeredQids)->map(function ($qid) use ($attempt, $questionsById, $locale) {
            $question = $questionsById->get($qid);
            if (!$question) {
                return null;
            }

            $userAnswer    = $attempt->getAnswerResult($qid);
            $correctAnswer = (int) $question->is_true;

            return [
                'question'       => $question,
                'localized_text' => $question->getLocalizedText($locale),
                'user_answer'    => $userAnswer,
                'correct_answer' => $correctAnswer,
                'is_correct'     => $userAnswer === $correctAnswer,
                'position'       => $attempt->getAnswerPosition($qid),
                'time_spent'     => $attempt->getTimeSpent($qid),
            ];
        })->filter()->values();

        // Ordina prima per `position` (chi ha risposto in quell'ordine), poi per id.
        $rows = $rows->sortBy([
            ['position', 'asc'],
            fn ($a, $b) => $a['question']->id <=> $b['question']->id,
        ])->values();

        $answered    = $rows->count();
        $correct     = $rows->filter(fn ($r) => $r['is_correct'])->count();
        $wrong       = $rows->filter(fn ($r) => !$r['is_correct'])->count();
        $notAnswered = max(0, $totalConfigured - $answered);

        // Criterio reale esame patente B: max errori da config.
        $maxErrors   = (int) config('simulator.max_errors');
        $totalErrors = $wrong + $notAnswered;
        $passed      = $totalErrors <= $maxErrors;

        $percentage = $totalConfigured > 0
            ? round($correct / $totalConfigured * 100, 1)
            : 0.0;

        $durationHuman = null;
        if ($attempt->duration) {
            $mins = intdiv($attempt->duration, 60);
            $secs = $attempt->duration % 60;
            $durationHuman = $mins > 0 ? "{$mins} min {$secs} sec" : "{$secs} sec";
        }

        return [
            'attempt' => $attempt,
            'rows'    => $rows,
            'stats'   => [
                'total'          => $totalConfigured,
                'answered'       => $answered,
                'correct'        => $correct,
                'wrong'          => $wrong,
                'not_answered'   => $notAnswered,
                'total_errors'   => $totalErrors,
                'max_errors'     => $maxErrors,
                'percentage'     => $percentage,
                'passed'         => $passed,
                'duration_human' => $durationHuman,
            ],
        ];
    }

    /**
     * Stessa logica di scoring del QuizAttemptService, replicata qui per non dipendere
     * da un Quiz preesistente. Confronto: answer.correct === question.is_true.
     */
    private function scoreAnswers(array $answers, $correctMap): int
    {
        $score = 0;

        foreach ($answers as $questionId => $answer) {
            if (!isset($correctMap[$questionId])) {
                continue;
            }

            $result = is_array($answer) ? (int) ($answer['correct'] ?? 0) : (int) $answer;

            if ($result === (int) $correctMap[$questionId]) {
                $score++;
            }
        }

        return $score;
    }

    /**
     * Converte il payload risposte nel formato esteso usato dalla view di dettaglio
     * tentativo (compatibile con QuizAttempt::getAnswerResult e con la pagina /quiz/attempts/{id}).
     */
    private function normalizeAnswers(array $answers): array
    {
        $normalized = [];

        foreach ($answers as $questionId => $answer) {
            if (is_array($answer)) {
                $toInt = fn ($v) => ($v !== null && $v !== '') ? (int) $v : null;
                $normalized[$questionId] = [
                    'correct'            => (int) ($answer['correct'] ?? 0),
                    'answered_at'        => $toInt($answer['answered_at'] ?? null),
                    'time_spent_seconds' => $toInt($answer['time_spent_seconds'] ?? null),
                    'position'           => $toInt($answer['position'] ?? null),
                ];
            } else {
                $normalized[$questionId] = [
                    'correct'            => (int) $answer,
                    'answered_at'        => null,
                    'time_spent_seconds' => null,
                    'position'           => null,
                ];
            }
        }

        return $normalized;
    }
}
