<?php

namespace App\Exports;

use App\Models\Question;
use Maatwebsite\Excel\Concerns\FromCollection;

class QuestionsExport implements FromCollection
{
    public function collection()
    {
//        return Question::all();

        return Question::with('category')->get()->map(function ($q) {
            return [
                'ID' => $q->id,
                'Categoria' => $q->category->name ?? '',
                'Domanda' => $q->question,
                'Risposta' => $q->is_true ? 'VERO' : 'FALSO',
                'Immagine' => $q->image,
            ];
        });
    }
}



