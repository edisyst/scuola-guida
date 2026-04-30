<?php

namespace App\Observers;

use App\Models\Quiz;

class QuizObserver
{
    public function creating(Quiz $quiz): void
    {
        // Se il title non è stato impostato manualmente, generalo
        if (empty($quiz->title)) {
            $quiz->title = 'QUIZ NR. ' . ($quiz->id ?? 'temp');
        }
    }

    public function created(Quiz $quiz): void
    {
        // Dopo la creazione, aggiorna il title con l'ID corretto
        if (str_starts_with($quiz->title, 'QUIZ NR. temp')) {
            $quiz->update(['title' => 'QUIZ NR. ' . $quiz->id]);
        }
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
    }
}
