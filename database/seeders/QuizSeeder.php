<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Quiz;
use App\Models\Question;
use App\Models\Category;

class QuizSeeder extends Seeder
{
    public function run(): void
    {
        $questions = Question::all();

        if ($questions->isEmpty()) {
            $categories = Category::all();

            if ($categories->isEmpty()) {
                $categories = Category::factory(5)->create();
            }

            $questions = Question::factory(100)->recycle($categories)->create();
        }

        $quizzes = Quiz::factory(10)->create();

        foreach ($quizzes as $quiz) {
            $count    = min($quiz->max_questions, $questions->count());
            $selected = $questions->random($count);

            // costruisce il pivot con il campo 'order'
            $pivot = $selected->values()->mapWithKeys(
                fn ($question, $index) => [$question->id => ['order' => $index + 1]]
            );

            $quiz->questions()->attach($pivot);
        }

        $this->command->info('CREATI 10 QUIZ CON DOMANDE ASSOCIATE');
    }
}
