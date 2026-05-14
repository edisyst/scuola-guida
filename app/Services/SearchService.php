<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Question;
use Illuminate\Support\Collection;

class SearchService
{
    /**
     * @return array{questions: Collection, categories: Collection}
     */
    public function search(string $term): array
    {
        $term = trim($term);

        if ($term === '') {
            return ['questions' => collect(), 'categories' => collect()];
        }

        return [
            'questions' => Question::with('category')
                ->where('question', 'like', "%{$term}%")
                ->orderBy('question')
                ->get(),

            'categories' => Category::where('name', 'like', "%{$term}%")
                ->orderBy('name')
                ->get(),
        ];
    }
}
