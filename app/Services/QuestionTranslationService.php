<?php

namespace App\Services;

use App\Models\Question;
use App\Models\QuestionTranslation;
use Illuminate\Support\Collection;

class QuestionTranslationService
{
    /**
     * Crea o aggiorna la traduzione per la coppia (question, locale).
     * Idempotente: l'indice unico (question_id, locale) garantisce una sola
     * riga per lingua; updateOrCreate evita violazioni di vincolo.
     */
    public function upsert(Question $question, string $locale, string $text): QuestionTranslation
    {
        return $question->translations()->updateOrCreate(
            ['locale' => $locale],
            ['text' => $text],
        );
    }

    public function delete(Question $question, string $locale): void
    {
        $question->translations()->where('locale', $locale)->delete();
    }

    /**
     * Tutte le traduzioni di una domanda, ordinate per lingua.
     */
    public function getForQuestion(Question $question): Collection
    {
        return $question->translations()->orderBy('locale')->get();
    }
}
