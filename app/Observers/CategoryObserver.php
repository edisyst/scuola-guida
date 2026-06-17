<?php

namespace App\Observers;

use App\Models\Category;
use Illuminate\Support\Facades\Cache;

class CategoryObserver
{
    public function saved(Category $category): void
    {
        Cache::forget('categories_list');
        clearAdminBadgesCache();
        clearDashboardKpiCache();
    }

    public function deleting(Category $category): void
    {
        \App\Models\StudyContent::where('studyable_type', Category::class)
                                ->where('studyable_id', $category->id)
                                ->each(fn ($c) => $c->delete());
    }

    public function deleted(Category $category): void
    {
        Cache::forget('categories_list');
        clearAdminBadgesCache();
        clearDashboardKpiCache();
    }
}
