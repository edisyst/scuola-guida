<?php

namespace App\Observers;

use App\Models\Question;

class QuestionObserver
{
    public function saved(Question $question): void
    {
        clearAdminBadgesCache();
    }

    public function deleted(Question $question): void
    {
        // Nota: fired solo su Model::delete(). Le bulk delete chiamano clearAdminBadgesCache() manualmente.
        clearAdminBadgesCache();
    }
}
