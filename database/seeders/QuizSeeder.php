<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Quiz;
use App\Models\Question;
use App\Models\Category;
use App\Models\User;

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

        $admin = User::first();

        $quizzes = collect()
            ->merge(Quiz::factory(3)->state(['status' => Quiz::STATUS_DRAFT])->create())
            ->merge(Quiz::factory(4)->state(['status' => Quiz::STATUS_PUBLISHED])->create())
            ->merge(
                Quiz::factory(3)->state(fn () => [
                    'status'       => Quiz::STATUS_CONFIRMED,
                    'confirmed_at' => now()->subDays(rand(1, 15)),
                    'confirmed_by' => $admin?->id,
                ])->create()
            );

        foreach ($quizzes as $quiz) {
            $count    = min($quiz->max_questions, $questions->count());
            $selected = $questions->random($count);

            $pivot = $selected->values()->mapWithKeys(
                fn ($question, $index) => [$question->id => ['order' => $index + 1]]
            );

            $quiz->questions()->attach($pivot);
        }

        $this->command->info('CREATI 10 QUIZ (3 bozze, 4 pubblicati, 3 esami) CON DOMANDE ASSOCIATE');
    }
}
