<?php

namespace App\Observers;

use App\Models\CategoryTranslation;

class CategoryTranslationObserver
{
    public function creating(CategoryTranslation $translation): void
    {
        if (auth()->check() && $translation->created_by === null) {
            $translation->created_by = auth()->id();
        }
    }
}
