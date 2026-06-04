<?php

namespace App\Services;

use App\Models\Category;
use App\Models\DiagnosticResult;
use App\Models\Question;
use App\Models\QuizAttempt;
use App\Models\User;
use Illuminate\Support\Collection;

class StudyPlanService
{
    /**
     * Costruisce il piano di studio dell'utente ordinato per padronanza ascendente.
     *
     * Ogni elemento della Collection restituita ha la forma:
     * {
     *   category: Category,
     *   mastery: int (0-100),
     *   attempts_count: int,
     *   recommended_action: string
     * }
     *
     * Zero side effects: nessuna scrittura al DB.
     */
    public function buildPlan(User $user): Collection
    {
        $categories = Category::with('translations')->whereHas('questions')->orderBy('name')->get()->keyBy('id');

        if ($categories->isEmpty()) {
            return collect();
        }

        // Pre-carica mappa question_id => category_id per aggregazione in PHP (evita N+1)
        $questionCategoryMap = Question::whereIn('category_id', $categories->keys())
            ->pluck('category_id', 'id');

        // Aggrega dati storici per categoria dai quiz attempts
        $historicalStats = $this->aggregateHistoricalStats($user, $questionCategoryMap);

        // Recupera risultati dell'ultimo test diagnostico per categoria
        $diagnosticResults = $this->latestDiagnosticByCategory($user);

        $plan = collect();

        foreach ($categories as $categoryId => $category) {
            $historical        = $historicalStats[$categoryId] ?? null;
            $diagnosticCorrect = $diagnosticResults[$categoryId] ?? null;

            $mastery       = $this->computeMastery($historical, $diagnosticCorrect);
            $attemptsCount = $historical ? $historical['total'] : 0;

            $plan->push([
                'category'           => $category,
                'mastery'            => $mastery,
                'attempts_count'     => $attemptsCount,
                'recommended_action' => $this->recommendedAction($mastery),
            ]);
        }

        return $plan->sortBy('mastery')->values();
    }

    // ──────────────────────────────────────────────────────────────────────────
    // INTERNALS
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * Aggrega totale risposte e risposte corrette per categoria dai QuizAttempt.
     * Ritorna: [category_id => ['total' => N, 'correct' => N]]
     */
    private function aggregateHistoricalStats(User $user, Collection $questionCategoryMap): array
    {
        $attempts = QuizAttempt::where('user_id', $user->id)
            ->select('answers')
            ->get();

        $stats = [];

        foreach ($attempts as $attempt) {
            foreach ($attempt->answers ?? [] as $questionId => $answer) {
                $categoryId = $questionCategoryMap[(int) $questionId] ?? null;

                if (!$categoryId) {
                    continue;
                }

                $stats[$categoryId] ??= ['total' => 0, 'correct' => 0];
                $stats[$categoryId]['total']++;

                $correct = is_array($answer) ? (int) ($answer['correct'] ?? 0) : (int) $answer;
                $stats[$categoryId]['correct'] += $correct;
            }
        }

        return $stats;
    }

    /**
     * Restituisce un array [category_id => bool(correct)] dall'ultimo batch diagnostico.
     */
    private function latestDiagnosticByCategory(User $user): array
    {
        $latest = DiagnosticResult::where('user_id', $user->id)
            ->orderBy('taken_at', 'desc')
            ->first();

        if (!$latest) {
            return [];
        }

        return DiagnosticResult::where('user_id', $user->id)
            ->where('batch_id', $latest->batch_id)
            ->get()
            ->mapWithKeys(fn ($r) => [$r->category_id => (bool) $r->correct])
            ->toArray();
    }

    /**
     * Calcola il punteggio di padronanza (0-100).
     *
     * - Solo storico: percentuale corrette
     * - Solo diagnostico: 60 se corretto, 20 se errato
     * - Entrambi: 70% storico + 30% diagnostico
     */
    private function computeMastery(?array $historical, ?bool $diagnosticCorrect): int
    {
        $hasHistorical = $historical !== null && $historical['total'] > 0;
        $hasDiagnostic = $diagnosticCorrect !== null;

        if (!$hasHistorical && !$hasDiagnostic) {
            return 0;
        }

        if ($hasHistorical && !$hasDiagnostic) {
            return (int) round(($historical['correct'] / $historical['total']) * 100);
        }

        $diagnosticScore = $diagnosticCorrect ? 60 : 20;

        if (!$hasHistorical) {
            return $diagnosticScore;
        }

        $historicalScore = ($historical['correct'] / $historical['total']) * 100;

        return (int) round($historicalScore * 0.7 + $diagnosticScore * 0.3);
    }

    private function recommendedAction(int $mastery): string
    {
        if ($mastery < 30) {
            return 'Inizia con questa categoria';
        }

        if ($mastery <= 70) {
            return 'Continua a esercitarti';
        }

        return 'Padronanza buona, ripassa occasionalmente';
    }
}
