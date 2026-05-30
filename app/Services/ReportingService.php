<?php

namespace App\Services;

use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\QuizEnrollment;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class ReportingService
{
    /** Memoizzata per evitare doppio caricamento in buildComparisonReport(). */
    private ?\Illuminate\Support\Collection $questionMap = null;

    public function buildPeriodReport(Carbon $from, Carbon $to): array
    {
        $from = $from->copy()->startOfDay();
        $to   = $to->copy()->endOfDay();

        // Periodi completamente passati: TTL 24h. Periodo corrente: TTL 5 min.
        $isPast = $to->lt(now()->startOfDay());
        $ttl    = $isPast ? 86400 : 300;
        $key    = "report_period_{$from->format('Ymd')}_{$to->format('Ymd')}";

        return Cache::remember($key, $ttl, fn () => $this->compute($from, $to));
    }

    public function buildComparisonReport(Carbon $from, Carbon $to): array
    {
        // Periodo precedente di pari durata (incluso)
        $days    = $from->diffInDays($to); // es. 30 per un mese di 31 giorni
        $prevTo  = $from->copy()->subDay();
        $prevFrom = $prevTo->copy()->subDays($days);

        $current  = $this->buildPeriodReport($from, $to);
        $previous = $this->buildPeriodReport($prevFrom, $prevTo);

        $delta = fn (?float $curr, ?float $prev): ?float =>
            ($curr !== null && $prev !== null && $prev != 0)
                ? round((($curr - $prev) / abs($prev)) * 100, 1)
                : null;

        return [
            'current'  => $current,
            'previous' => $previous,
            'period'   => [
                'from'      => $from,
                'to'        => $to,
                'prev_from' => $prevFrom,
                'prev_to'   => $prevTo,
            ],
            'delta' => [
                'total_attempts'  => $delta($current['total_attempts'], $previous['total_attempts']),
                'active_students' => $delta($current['active_students'], $previous['active_students']),
                'pass_rate'       => $delta($current['pass_rate'], $previous['pass_rate']),
                'average_score'   => $delta($current['average_score'], $previous['average_score']),
            ],
        ];
    }

    private function compute(Carbon $from, Carbon $to): array
    {
        // Aggregazioni scalari SQL su quiz confermati
        $agg = DB::table('quiz_attempts as qa')
            ->join('quizzes as q', 'qa.quiz_id', '=', 'q.id')
            ->where('q.status', Quiz::STATUS_CONFIRMED)
            ->whereBetween('qa.created_at', [$from, $to])
            ->where('qa.total_questions', '>', 0)
            ->selectRaw('
                COUNT(*)                                                                                                       AS total_attempts,
                COUNT(DISTINCT qa.user_id)                                                                                    AS active_students,
                AVG(CAST(qa.score AS REAL) / qa.total_questions * 100)                                                        AS average_score,
                SUM(CASE WHEN (qa.total_questions - qa.score) <= q.max_errors THEN 1 ELSE 0 END)                              AS passed_count
            ')
            ->first();

        $total      = (int)   ($agg->total_attempts ?? 0);
        $active     = (int)   ($agg->active_students ?? 0);
        $avg        = $total > 0 ? round((float) $agg->average_score, 1) : null;
        $passRate   = $total > 0 ? round((float) $agg->passed_count / $total * 100, 1) : null;

        // Iscrizioni approvate/completate nel periodo (data approvazione)
        $enrollmentsCount = QuizEnrollment::whereBetween('reviewed_at', [$from, $to])
            ->whereIn('status', [QuizEnrollment::STATUS_APPROVED, QuizEnrollment::STATUS_COMPLETED])
            ->count();

        // Tentativi per giorno (serie temporale per il grafico)
        $byDay = DB::table('quiz_attempts as qa')
            ->join('quizzes as q', 'qa.quiz_id', '=', 'q.id')
            ->where('q.status', Quiz::STATUS_CONFIRMED)
            ->whereBetween('qa.created_at', [$from, $to])
            ->selectRaw('DATE(qa.created_at) AS date, COUNT(*) AS count')
            ->groupByRaw('DATE(qa.created_at)')
            ->orderBy('date')
            ->get()
            ->keyBy('date');

        $attemptsPerDay = [];
        $cursor = $from->copy()->startOfDay();
        while ($cursor->lte($to)) {
            $key = $cursor->format('Y-m-d');
            $attemptsPerDay[] = [
                'date'  => $cursor->format('d/m'),
                'count' => isset($byDay[$key]) ? (int) $byDay[$key]->count : 0,
            ];
            $cursor->addDay();
        }

        [$outcomesByCategory, $mostFailedQuestions] = $this->computeAnswerMetrics($from, $to);

        return [
            'total_attempts'        => $total,
            'active_students'       => $active,
            'pass_rate'             => $passRate,
            'average_score'         => $avg,
            'outcomes_by_category'  => $outcomesByCategory,
            'most_failed_questions' => $mostFailedQuestions,
            'enrollments_count'     => $enrollmentsCount,
            'attempts_per_day'      => $attemptsPerDay,
        ];
    }

    /**
     * Calcola distribuzione corrette/sbagliate per categoria e top 20 domande
     * più sbagliate. Carica le risposte in chunk (lazy) per evitare OOM.
     *
     * @return array{0: list<array>, 1: list<array>}
     */
    private function computeAnswerMetrics(Carbon $from, Carbon $to): array
    {
        // Mappa domanda → categoria: caricata una volta, riusata tra le due chiamate
        // di buildComparisonReport() quando la cache è fredda per entrambi i periodi.
        $this->questionMap ??= DB::table('questions')
            ->join('categories', 'questions.category_id', '=', 'categories.id')
            ->select('questions.id', 'questions.question', 'categories.id as cat_id', 'categories.name as cat_name')
            ->get()
            ->keyBy('id');

        $questionMap = $this->questionMap;

        $catAccum = []; // [cat_id => ['name', 'correct', 'incorrect']]
        $qAccum   = []; // [q_id   => ['question', 'category', 'errors']]

        QuizAttempt::join('quizzes', 'quiz_attempts.quiz_id', '=', 'quizzes.id')
            ->where('quizzes.status', Quiz::STATUS_CONFIRMED)
            ->whereBetween('quiz_attempts.created_at', [$from, $to])
            ->whereNotNull('quiz_attempts.answers')
            ->select('quiz_attempts.*')
            ->orderBy('quiz_attempts.id')
            ->lazy(200)
            ->each(function (QuizAttempt $attempt) use (&$catAccum, &$qAccum, $questionMap): void {
                foreach ($attempt->answers ?? [] as $qId => $raw) {
                    $result = $attempt->getAnswerResult($qId);
                    if ($result === null) {
                        continue;
                    }

                    $q = $questionMap->get((int) $qId);
                    if (!$q) {
                        continue;
                    }

                    if (!isset($catAccum[$q->cat_id])) {
                        $catAccum[$q->cat_id] = [
                            'name'      => $q->cat_name,
                            'correct'   => 0,
                            'incorrect' => 0,
                        ];
                    }

                    if ($result === 1) {
                        $catAccum[$q->cat_id]['correct']++;
                    } else {
                        $catAccum[$q->cat_id]['incorrect']++;

                        if (!isset($qAccum[$qId])) {
                            $qAccum[$qId] = [
                                'question' => $q->question,
                                'category' => $q->cat_name,
                                'errors'   => 0,
                            ];
                        }
                        $qAccum[$qId]['errors']++;
                    }
                }
            });

        // Ordina categorie per totale risposte desc
        uasort($catAccum, fn ($a, $b) => ($b['correct'] + $b['incorrect']) <=> ($a['correct'] + $a['incorrect']));

        // Top 20 domande più sbagliate
        usort($qAccum, fn ($a, $b) => $b['errors'] <=> $a['errors']);

        return [array_values($catAccum), array_slice($qAccum, 0, 20)];
    }
}
