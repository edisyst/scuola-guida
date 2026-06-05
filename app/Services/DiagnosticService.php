<?php

namespace App\Services;

use App\Models\Category;
use App\Models\DiagnosticResult;
use App\Models\Question;
use App\Models\QuizAttempt;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DiagnosticService
{
    /**
     * Restituisce una Collection di Question, una per categoria attiva.
     * Esclude le domande risposte nelle ultime 24h, rilevate dai quiz_attempts.
     * Filtra le categorie per il tipo di patente attivo dell'utente.
     */
    public function generateQuestions(User $user): Collection
    {
        $seenIds     = $this->recentlySeenQuestionIds($user);
        $licenseType = $user->getActiveLicenseType();

        $categoryQuery = Category::whereHas('questions');

        if ($licenseType) {
            $categoryQuery->whereHas('licenseTypes', fn($q) => $q->where('license_types.id', $licenseType->id));
        }

        $categories = $categoryQuery->get();

        $questions = collect();

        foreach ($categories as $category) {
            $query = $category->questions()->inRandomOrder();

            if (!empty($seenIds)) {
                $query->whereNotIn('id', $seenIds);
            }

            $question = $query->first();

            if ($question) {
                $questions->push($question);
            }
        }

        return $questions;
    }

    /**
     * Persiste i risultati del test diagnostico in una singola transazione.
     * $answers: [question_id => 0|1]
     */
    public function saveResults(User $user, array $answers): void
    {
        if (empty($answers)) {
            return;
        }

        $questionIds = array_keys($answers);
        $categoryMap = Question::whereIn('id', $questionIds)->pluck('category_id', 'id');

        $batchId = (string) Str::uuid();
        $now     = now();

        DB::transaction(function () use ($user, $answers, $categoryMap, $batchId, $now) {
            foreach ($answers as $questionId => $correct) {
                $categoryId = $categoryMap[(int) $questionId] ?? null;

                if (!$categoryId) {
                    continue;
                }

                DiagnosticResult::create([
                    'user_id'     => $user->id,
                    'category_id' => $categoryId,
                    'correct'     => (bool) $correct,
                    'taken_at'    => $now,
                    'batch_id'    => $batchId,
                ]);
            }
        });
    }

    /**
     * Recupera l'ultimo gruppo di risultati diagnostici dell'utente,
     * raggruppati per batch_id. Null se l'utente non ha mai fatto il test.
     */
    public function getLatestDiagnostic(User $user): ?Collection
    {
        $latest = DiagnosticResult::where('user_id', $user->id)
            ->orderBy('taken_at', 'desc')
            ->first();

        if (!$latest) {
            return null;
        }

        return DiagnosticResult::where('user_id', $user->id)
            ->where('batch_id', $latest->batch_id)
            ->with('category')
            ->get();
    }

    /**
     * Helper rapido: l'utente ha almeno un risultato diagnostico?
     */
    public function hasDiagnostic(User $user): bool
    {
        return DiagnosticResult::where('user_id', $user->id)->exists();
    }

    /**
     * Restituisce gli ID delle domande risposte dall'utente nelle ultime 24h,
     * estraendoli dagli array answers dei QuizAttempt recenti.
     * Usa get() per garantire che il cast 'array' sia applicato alle answers.
     */
    private function recentlySeenQuestionIds(User $user): array
    {
        $attempts = QuizAttempt::where('user_id', $user->id)
            ->where('created_at', '>=', now()->subHours(24))
            ->get(['answers']);

        $ids = collect();

        foreach ($attempts as $attempt) {
            if (!is_array($attempt->answers)) {
                continue;
            }
            $ids = $ids->merge(array_map('intval', array_keys($attempt->answers)));
        }

        return $ids->unique()->toArray();
    }
}
