<?php

namespace App\Imports;

use App\Models\Question;
use App\Models\Category;
use Maatwebsite\Excel\Concerns\ToModel;

class QuestionsImport implements ToModel
{
    public function model(array $row)
    {
        if ($row[0] === 'ID') return null; // skip header

        $category = Category::firstOrCreate([
            'name' => $row[1]
        ]);

        return new Question([
            'category_id' => $category->id,
            'question' => $row[2],
            'is_true' => strtoupper(trim($row[3])) === 'VERO',
            'image' => $row[4] ?? null,
        ]);
    }
}
