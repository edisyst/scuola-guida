<?php

namespace App\Services;

use App\Models\LearnedQuestion;
use App\Models\QuestionReview;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class SpacedRepetitionService
{
    private const MAX_INTERVAL       = 365;
    private const MAX_EASE           = 2.80;
    private const MIN_EASE           = 1.30;
    private const MASTERED_REP       = 5;
    private const UPCOMING_CACHE_TTL = 300;

    public static function upcomingCacheKey(int $userId): string
    {
        return "sr_upcoming_{$userId}";
    }

    public function recordAnswer(User $user, int $questionId, bool $correct): QuestionReview
    {
        $review = QuestionReview::firstOrCreate(
            ['user_id' => $user->id, 'question_id' => $questionId],
            [
                'next_review_at' => now(),
                'interval_days'  => 1,
                'ease_factor'    => 2.50,
                'repetitions'    => 0,
            ]
        );

        $updated = $this->calculateNextReview($review, $correct);
        $review->fill($updated)->save();

        Cache::forget(self::upcomingCacheKey($user->id));

        return $review->fresh();
    }

    /**
     * Calcola i nuovi valori SM-2 senza persistere — utile per test e preview.
     *
     * @return array{interval_days: int, ease_factor: float, repetitions: int, next_review_at: \Illuminate\Support\Carbon, last_reviewed_at: \Illuminate\Support\Carbon}
     */
    public function calculateNextReview(QuestionReview $review, bool $correct): array
    {
        $intervalDays = $review->interval_days;
        $easeFactor   = (float) $review->ease_factor;
        $repetitions  = $review->repetitions;

        if ($correct) {
            $intervalDays = match (true) {
                $repetitions === 0 => 1,
                $repetitions === 1 => 3,
                default            => (int) min(round($intervalDays * $easeFactor), self::MAX_INTERVAL),
            };
            $repetitions++;
            $easeFactor = min($easeFactor + 0.10, self::MAX_EASE);
        } else {
            $intervalDays = 1;
            $repetitions  = 0;
            $easeFactor   = max($easeFactor - 0.20, self::MIN_EASE);
        }

        return [
            'interval_days'    => $intervalDays,
            'ease_factor'      => round($easeFactor, 2),
            'repetitions'      => $repetitions,
            'next_review_at'   => now()->addDays($intervalDays),
            'last_reviewed_at' => now(),
        ];
    }

    /**
     * Domande in scadenza (next_review_at <= now), con eager loading, escludendo
     * quelle già in learned_questions. Carica gli ID imparati in memoria per
     * evitare subquery nel ciclo (zero N+1). Filtra per il tipo di patente attivo.
     */
    public function getDueQuestions(User $user, ?int $categoryId = null, int $limit = 30): Collection
    {
        // Pre-carica in memoria: subquery evitabile perché il numero è tipicamente piccolo.
        $learnedIds  = LearnedQuestion::where('user_id', $user->id)->pluck('question_id');
        $licenseType = $user->getActiveLicenseType();

        $query = QuestionReview::with(['question.category'])
            ->where('user_id', $user->id)
            ->where('next_review_at', '<=', now())
            ->whereNotIn('question_id', $learnedIds)
            ->orderBy('next_review_at', 'asc');

        if ($categoryId !== null) {
            $query->whereHas('question', fn ($q) => $q->where('category_id', $categoryId));
        }

        if ($licenseType) {
            $query->whereHas('question', fn ($q) =>
                $q->whereHas('category', fn ($cq) =>
                    $cq->whereHas('licenseTypes', fn ($lq) => $lq->where('license_types.id', $licenseType->id))
                )
            );
        }

        return $query->limit($limit)->get();
    }

    /**
     * Contatori per la sidebar / dashboard: domande in scadenza oggi, domani, questa settimana.
     * Cached per UPCOMING_CACHE_TTL secondi; invalidata da recordAnswer() e da markAsLearned/unmarkAsLearned.
     *
     * @return array{due_today: int, due_tomorrow: int, due_this_week: int}
     */
    public function getUpcomingCount(User $user): array
    {
        return Cache::remember(self::upcomingCacheKey($user->id), self::UPCOMING_CACHE_TTL, function () use ($user) {
            $learnedIds = LearnedQuestion::where('user_id', $user->id)->pluck('question_id');

            $base = QuestionReview::where('user_id', $user->id)
                ->whereNotIn('question_id', $learnedIds);

            return [
                'due_today'     => (clone $base)->where('next_review_at', '<=', now()->endOfDay())->count(),
                'due_tomorrow'  => (clone $base)->whereBetween('next_review_at', [
                    now()->startOfDay()->addDay(),
                    now()->endOfDay()->addDay(),
                ])->count(),
                'due_this_week' => (clone $base)->where('next_review_at', '<=', now()->endOfWeek())->count(),
            ];
        });
    }

    /**
     * Statistiche aggregate per la pagina overview.
     *
     * @return array{total_tracked: int, mastered: int, learning: int, new: int}
     */
    public function getStats(User $user): array
    {
        $rows = QuestionReview::where('user_id', $user->id)
            ->selectRaw('repetitions, COUNT(*) as cnt')
            ->groupBy('repetitions')
            ->pluck('cnt', 'repetitions');

        $total    = (int) $rows->sum();
        $mastered = (int) $rows->filter(fn ($cnt, $rep) => $rep >= self::MASTERED_REP)->sum();
        $new      = (int) ($rows->get(0) ?? 0);
        $learning = $total - $mastered - $new;

        return [
            'total_tracked' => $total,
            'mastered'      => $mastered,
            'learning'      => $learning,
            'new'           => $new,
        ];
    }

    /**
     * Conteggio domande in scadenza oggi per categoria — usato nel piano di studio.
     * Filtra per il tipo di patente attivo.
     *
     * @return array<int, int>  [category_id => count]
     */
    public function getDueCountByCategory(User $user): array
    {
        $learnedIds  = LearnedQuestion::where('user_id', $user->id)->pluck('question_id');
        $licenseType = $user->getActiveLicenseType();

        $query = QuestionReview::where('question_reviews.user_id', $user->id)
            ->where('next_review_at', '<=', now())
            ->whereNotIn('question_reviews.question_id', $learnedIds)
            ->join('questions', 'questions.id', '=', 'question_reviews.question_id')
            ->selectRaw('questions.category_id, COUNT(*) as cnt')
            ->groupBy('questions.category_id');

        if ($licenseType) {
            $query->join('category_license_type', function ($join) use ($licenseType) {
                $join->on('category_license_type.category_id', '=', 'questions.category_id')
                     ->where('category_license_type.license_type_id', '=', $licenseType->id);
            });
        }

        return $query->pluck('cnt', 'questions.category_id')->all();
    }
}
