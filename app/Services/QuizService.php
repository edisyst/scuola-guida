<?php

namespace App\Services;

use App\Models\Question;
use App\Repositories\QuestionRepository;

class QuizService
{
    public function __construct(private QuestionRepository $repo) {}

    public function calculateScore(array $answers): int
    {
        $score = 0;

        foreach ($answers as $questionId => $answer) {

            $question = Question::find($questionId);

            if ($question && $question->is_true == $answer) {
                $score++;
            }
        }

        return $score;
    }
}
