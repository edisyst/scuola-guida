<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Category;
use App\Models\LicenseType;
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
    public function getProductionMetrics(?User $editor, Carbon $from, Carbon $to, ?LicenseType $licenseType = null): array
    {
        $from = $from->copy()->startOfDay();
        $to   = $to->copy()->endOfDay();

        $isPast    = $to->lt(now()->startOfDay());
        $ttl       = $isPast ? 86400 : 300;
        $editorKey = $editor ? $editor->id : 'all';
        $ltKey     = $licenseType?->id ?? 'all';
        $cacheKey  = "editor_metrics_{$editorKey}_{$from->format('Ymd')}_{$to->format('Ymd')}_{$ltKey}";

        return Cache::remember($cacheKey, $ttl, function () use ($editor, $from, $to, $licenseType) {
            $filteredQuestionIds = $licenseType
                ? Question::whereHas('category.licenseTypes',
                    fn ($q) => $q->where('license_types.id', $licenseType->id)
                  )->pluck('id')
                : null;

            $filteredQuizIds = $licenseType
                ? Quiz::where('license_type_id', $licenseType->id)->pluck('id')
                : null;

            return [
                'questions_created' => $this->countAuditEvents($editor, Question::class, 'created', $from, $to, $filteredQuestionIds),
                'questions_updated' => $this->countAuditEvents($editor, Question::class, 'updated', $from, $to, $filteredQuestionIds),
                'quizzes_published' => $this->countQuizTransitions($editor, Quiz::STATUS_PUBLISHED, $from, $to, $filteredQuizIds),
                'quizzes_confirmed' => $this->countQuizzesConfirmed($editor, $from, $to, $filteredQuizIds),
                'activity_by_day'   => $this->getActivityByDay($editor, $from, $to, $filteredQuestionIds, $filteredQuizIds),
            ];
        });
    }

    /**
     * Metriche globali sullo stato dei contenuti (non per-editor, non time-filtered).
     */
    public function getGlobalContentMetrics(?LicenseType $licenseType = null): array
    {
        $ltKey = $licenseType?->id ?? 'all';
        $cacheKey = "editor_global_metrics_{$ltKey}";

        return Cache::remember($cacheKey, 300, function () use ($licenseType) {
            return [
                'categories_by_question_count' => $this->getCategoriesByQuestionCount($licenseType),
                'most_reported_questions'       => $this->getMostReportedQuestions($licenseType),
                'quizzes_by_state'              => $this->getQuizzesByState($licenseType),
                'questions_without_image'       => $this->countQuestionsWithoutImage($licenseType),
                'recently_reported'             => $this->getRecentlyReported($licenseType),
            ];
        });
    }

    // ─────────────────────────────────────────────────────────────────────────
    // PRODUZIONE PER-EDITOR
    // ─────────────────────────────────────────────────────────────────────────

    private function countAuditEvents(?User $editor, string $modelType, string $event, Carbon $from, Carbon $to, ?Collection $filteredIds = null): int
    {
        $q = AuditLog::where('event', $event)
            ->where('model_type', $modelType)
            ->whereBetween('created_at', [$from, $to]);

        if ($filteredIds !== null) {
            $q->whereIn('model_id', $filteredIds);
        }

        $this->applyEditorFilter($q, $editor);

        return $q->count();
    }

    private function countQuizTransitions(?User $editor, string $status, Carbon $from, Carbon $to, ?Collection $filteredIds = null): int
    {
        $q = AuditLog::where('event', 'updated')
            ->where('model_type', Quiz::class)
            ->whereBetween('created_at', [$from, $to])
            ->whereRaw("JSON_EXTRACT(new_values, '$.status') = ?", [$status]);

        if ($filteredIds !== null) {
            $q->whereIn('model_id', $filteredIds);
        }

        $this->applyEditorFilter($q, $editor);

        return $q->count();
    }

    /** Usa confirmed_by + confirmed_at già presenti sul model: più affidabile dell'audit log. */
    private function countQuizzesConfirmed(?User $editor, Carbon $from, Carbon $to, ?Collection $filteredIds = null): int
    {
        $q = Quiz::whereNotNull('confirmed_by')
            ->whereNotNull('confirmed_at')
            ->whereBetween('confirmed_at', [$from, $to]);

        if ($filteredIds !== null) {
            $q->whereIn('id', $filteredIds);
        }

        if ($editor) {
            $q->where('confirmed_by', $editor->id);
        } else {
            $q->whereIn('confirmed_by', User::where('role', User::ROLE_EDITOR)->pluck('id'));
        }

        return $q->count();
    }

    private function getActivityByDay(?User $editor, Carbon $from, Carbon $to, ?Collection $filteredQuestionIds = null, ?Collection $filteredQuizIds = null): array
    {
        $q = AuditLog::whereIn('model_type', [Question::class, Quiz::class])
            ->whereBetween('created_at', [$from, $to]);

        if ($filteredQuestionIds !== null || $filteredQuizIds !== null) {
            $q->where(function ($query) use ($filteredQuestionIds, $filteredQuizIds) {
                if ($filteredQuestionIds !== null) {
                    $query->orWhere(function ($q2) use ($filteredQuestionIds) {
                        $q2->where('model_type', Question::class)
                           ->whereIn('model_id', $filteredQuestionIds);
                    });
                }
                if ($filteredQuizIds !== null) {
                    $query->orWhere(function ($q2) use ($filteredQuizIds) {
                        $q2->where('model_type', Quiz::class)
                           ->whereIn('model_id', $filteredQuizIds);
                    });
                }
            });
        }

        $q->selectRaw('DATE(created_at) as date, COUNT(*) as total')
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

    private function getCategoriesByQuestionCount(?LicenseType $licenseType = null): Collection
    {
        return Category::withCount('questions')
            ->when($licenseType, fn ($q) => $q->whereHas('licenseTypes', fn ($q2) => $q2->where('license_types.id', $licenseType->id)))
            ->orderByDesc('questions_count')
            ->get();
    }

    private function getMostReportedQuestions(?LicenseType $licenseType = null): Collection
    {
        return Question::select(['id', 'question'])
            ->withCount(['reports as pending_reports_count' => fn ($q) => $q->where('status', 'pending')])
            ->whereHas('reports', fn ($q) => $q->where('status', 'pending'))
            ->when($licenseType, fn ($q) => $q->whereHas('category.licenseTypes', fn ($q2) => $q2->where('license_types.id', $licenseType->id)))
            ->orderByDesc('pending_reports_count')
            ->limit(10)
            ->get();
    }

    private function getQuizzesByState(?LicenseType $licenseType = null): array
    {
        return Quiz::selectRaw('status, COUNT(*) as total')
            ->when($licenseType, fn ($q) => $q->where('license_type_id', $licenseType->id))
            ->groupBy('status')
            ->orderBy('status')
            ->pluck('total', 'status')
            ->toArray();
    }

    private function countQuestionsWithoutImage(?LicenseType $licenseType = null): int
    {
        return Question::where(fn ($q) => $q->whereNull('image')->orWhere('image', ''))
            ->when($licenseType, fn ($q) => $q->whereHas('category.licenseTypes', fn ($q2) => $q2->where('license_types.id', $licenseType->id)))
            ->count();
    }

    private function getRecentlyReported(?LicenseType $licenseType = null): Collection
    {
        return QuestionReport::with(['question:id,question', 'user:id,name'])
            ->where('status', 'pending')
            ->when($licenseType, fn ($q) => $q->whereHas('question.category.licenseTypes', fn ($q2) => $q2->where('license_types.id', $licenseType->id)))
            ->latest()
            ->limit(5)
            ->get();
    }
}
