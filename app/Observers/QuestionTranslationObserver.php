<?php

namespace App\Observers;

use App\Models\QuestionTranslation;

class QuestionTranslationObserver
{
    public function creating(QuestionTranslation $translation): void
    {
        if (auth()->check() && $translation->created_by === null) {
            $translation->created_by = auth()->id();
        }
    }
}
