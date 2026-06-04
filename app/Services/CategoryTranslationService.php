<?php

namespace App\Services;

use App\Models\Category;
use App\Models\CategoryTranslation;
use Illuminate\Database\Eloquent\Collection;

class CategoryTranslationService
{
    public function upsert(Category $category, string $locale, string $name): CategoryTranslation
    {
        return CategoryTranslation::updateOrCreate(
            ['category_id' => $category->id, 'locale' => $locale],
            ['name' => $name],
        );
    }

    public function delete(Category $category, string $locale): void
    {
        CategoryTranslation::where('category_id', $category->id)
            ->where('locale', $locale)
            ->delete();
    }

    public function getForCategory(Category $category): Collection
    {
        return CategoryTranslation::where('category_id', $category->id)
            ->orderBy('locale')
            ->get();
    }
}
