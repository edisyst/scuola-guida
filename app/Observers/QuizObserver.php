<?php

namespace App\Observers;

use App\Models\Quiz;

class QuizObserver
{
    public function creating(Quiz $quiz): void
    {
        if (empty($quiz->title)) {
            $quiz->title = 'QUIZ NR. ' . ($quiz->id ?? 'temp');
        }
    }

    public function created(Quiz $quiz): void
    {
        if (str_starts_with($quiz->title, 'QUIZ NR. temp')) {
            $quiz->update(['title' => 'QUIZ NR. ' . $quiz->id]);
        }

        clearAdminBadgesCache();
        clearDashboardKpiCache();
    }

    public function updating(Quiz $quiz): void
    {
        if (empty($quiz->title)) {
            $quiz->title = 'QUIZ NR. ' . ($quiz->id ?? 'temp');
        }
    }

    public function updated(Quiz $quiz): void
    {
        if (str_starts_with($quiz->title, 'QUIZ NR. temp')) {
            $quiz->update(['title' => 'QUIZ NR. ' . $quiz->id]);
        }

        clearAdminBadgesCache();
    }

    public function deleted(Quiz $quiz): void
    {
        // Nota: fired solo su Model::delete(). Le bulk delete chiamano clearAdminBadgesCache() manualmente.
        clearAdminBadgesCache();
        clearDashboardKpiCache();
    }
}
