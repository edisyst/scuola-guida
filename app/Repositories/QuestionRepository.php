<?php

namespace App\Repositories;

use App\Models\Question;

class QuestionRepository
{
    public function find(int $id): ?Question
    {
        return Question::find($id);
    }
}
