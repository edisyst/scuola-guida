<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Category;
use App\Models\Question;
use App\Models\Quiz;
use App\Models\QuestionReport;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class EditorMetricsService
{
    /**
     * Metriche di produzione per un singolo editor (o aggregato di tutti gli editor se $editor = null).
     */
    public function getProductionMetrics(?User $editor, Carbon $from, Carbon $to): array
    {
        $from = $from->copy()->startOfDay();
        $to   = $to->copy()->endOfDay();

        $isPast    = $to->lt(now()->startOfDay());
        $ttl       = $isPast ? 86400 : 300;
        $editorKey = $editor ? $editor->id : 'all';
        $cacheKey  = "editor_metrics_{$editorKey}_{$from->format('Ymd')}_{$to->format('Ymd')}";

        return Cache::remember($cacheKey, $ttl, function () use ($editor, $from, $to) {
            return [
                'questions_created' => $this->countAuditEvents($editor, Question::class, 'created', $from, $to),
                'questions_updated' => $this->countAuditEvents($editor, Question::class, 'updated', $from, $to),
                'quizzes_published' => $this->countQuizTransitions($editor, Quiz::STATUS_PUBLISHED, $from, $to),
                'quizzes_confirmed' => $this->countQuizzesConfirmed($editor, $from, $to),
                'activity_by_day'   => $this->getActivityByDay($editor, $from, $to),
            ];
        });
    }

    /**
     * Metriche globali sullo stato dei contenuti (non per-editor, non time-filtered).
     */
    public function getGlobalContentMetrics(): array
    {
        return Cache::remember('editor_global_metrics', 300, function () {
            return [
                'categories_by_question_count' => $this->getCategoriesByQuestionCount(),
                'most_reported_questions'       => $this->getMostReportedQuestions(),
                'quizzes_by_state'              => $this->getQuizzesByState(),
                'questions_without_image'       => $this->countQuestionsWithoutImage(),
                'recently_reported'             => $this->getRecentlyReported(),
            ];
        });
    }

    // ─────────────────────────────────────────────────────────────────────────
    // PRODUZIONE PER-EDITOR
    // ─────────────────────────────────────────────────────────────────────────

    private function countAuditEvents(?User $editor, string $modelType, string $event, Carbon $from, Carbon $to): int
    {
        $q = AuditLog::where('event', $event)
            ->where('model_type', $modelType)
            ->whereBetween('created_at', [$from, $to]);

        $this->applyEditorFilter($q, $editor);

        return $q->count();
    }

    private function countQuizTransitions(?User $editor, string $status, Carbon $from, Carbon $to): int
    {
        $q = AuditLog::where('event', 'updated')
            ->where('model_type', Quiz::class)
            ->whereBetween('created_at', [$from, $to])
            ->whereRaw("JSON_EXTRACT(new_values, '$.status') = ?", [$status]);

        $this->applyEditorFilter($q, $editor);

        return $q->count();
    }

    /** Usa confirmed_by + confirmed_at già presenti sul model: più affidabile dell'audit log. */
    private function countQuizzesConfirmed(?User $editor, Carbon $from, Carbon $to): int
    {
        $q = Quiz::whereNotNull('confirmed_by')
            ->whereNotNull('confirmed_at')
            ->whereBetween('confirmed_at', [$from, $to]);

        if ($editor) {
            $q->where('confirmed_by', $editor->id);
        } else {
            $q->whereIn('confirmed_by', User::where('role', User::ROLE_EDITOR)->pluck('id'));
        }

        return $q->count();
    }

    private function getActivityByDay(?User $editor, Carbon $from, Carbon $to): array
    {
        $q = AuditLog::whereIn('model_type', [Question::class, Quiz::class])
            ->whereBetween('created_at', [$from, $to])
            ->selectRaw('DATE(created_at) as date, COUNT(*) as total')
            ->groupBy('date')
            ->orderBy('date');

        $this->applyEditorFilter($q, $editor);

        $rows = $q->get()->keyBy('date');

        $result = [];
        $cursor = $from->copy()->startOfDay();
        while ($cursor->lte($to)) {
            $dateStr  = $cursor->format('Y-m-d');
            $result[] = [
                'date'  => $cursor->format('d/m'),
                'total' => isset($rows[$dateStr]) ? (int) $rows[$dateStr]->total : 0,
            ];
            $cursor->addDay();
        }

        return $result;
    }

    private function applyEditorFilter(\Illuminate\Database\Eloquent\Builder $q, ?User $editor): void
    {
        if ($editor) {
            $q->where('user_id', $editor->id);
        } else {
            $q->whereIn('user_id', User::where('role', User::ROLE_EDITOR)->pluck('id'));
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // STATO GLOBALE CONTENUTI
    // ─────────────────────────────────────────────────────────────────────────

    private function getCategoriesByQuestionCount(): Collection
    {
        return Category::withCount('questions')
            ->orderByDesc('questions_count')
            ->get();
    }

    private function getMostReportedQuestions(): Collection
    {
        return Question::select(['id', 'question'])
            ->withCount(['reports as pending_reports_count' => fn ($q) => $q->where('status', 'pending')])
            ->whereHas('reports', fn ($q) => $q->where('status', 'pending'))
            ->orderByDesc('pending_reports_count')
            ->limit(10)
            ->get();
    }

    private function getQuizzesByState(): array
    {
        return Quiz::selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->orderBy('status')
            ->pluck('total', 'status')
            ->toArray();
    }

    private function countQuestionsWithoutImage(): int
    {
        return Question::where(fn ($q) => $q->whereNull('image')->orWhere('image', ''))->count();
    }

    private function getRecentlyReported(): Collection
    {
        return QuestionReport::with(['question:id,question', 'user:id,name'])
            ->where('status', 'pending')
            ->latest()
            ->limit(5)
            ->get();
    }
}
