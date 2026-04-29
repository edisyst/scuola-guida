<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\QuizAttempt;
use App\Models\User;
use App\Models\Quiz;

class QuizAttemptSeeder extends Seeder
{
    public function run(): void
    {
        $users = User::all();
        $quizzes = Quiz::all();

        if ($quizzes->isEmpty()) {
            // fallback se non hai quiz
            $quizzes = Quiz::factory(5)->create();
        }

        foreach ($users as $user) {

            // ogni utente fa tra 3 e 10 quiz
            for ($i = 0; $i < rand(3, 10); $i++) {

                QuizAttempt::create([
                    'user_id' => $user->id,
                    'quiz_id' => $quizzes->random()->id,
                    'score' => rand(3, 10),
                    'total_questions' => 10,
                    'created_at' => now()->subDays(rand(0, 30)),
                ]);
            }
        }
    }
}
