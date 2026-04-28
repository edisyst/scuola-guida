<?php

namespace App\Imports;

use App\Models\Question;
use App\Models\Category;
use Maatwebsite\Excel\Concerns\ToModel;

class QuestionsImport implements ToModel
{
    public function model(array $row)
    {
        if ($row[0] === 'ID') return null; // salta header

        // NORMALIZZAZIONE RISPOSTA: accetta vero,VERO,TRUE,1,null
        $isTrue = strtoupper(trim($row[3] ?? ''));
        $isTrue = match ($isTrue) {
            'VERO', 'TRUE', '1' => true,
            'FALSO', 'FALSE', '0' => false,
            default => false,
        };

        // categoria (creata se non esiste)
        $category = Category::firstOrCreate(['name' => $row[1]]);

        return new Question([
            'category_id' => $category->id,
            'question' => $row[2],
            'is_true' => $isTrue, // 👈 usa la variabile
            'image' => $row[4] ?? null,
        ]);

    }
}
