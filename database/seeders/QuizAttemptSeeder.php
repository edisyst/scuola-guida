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
        $users   = User::all();
        $quizzes = Quiz::with('questions')->get();

        if ($quizzes->isEmpty()) {
            $this->call(QuizSeeder::class);
            $quizzes = Quiz::with('questions')->get();
        }

        foreach ($users as $user) {
            $attemptsCount = rand(3, 10);

            for ($i = 0; $i < $attemptsCount; $i++) {
                $quiz           = $quizzes->random();
                $quizQuestions  = $quiz->questions;
                $totalQuestions = $quizQuestions->count();

                if ($totalQuestions === 0) {
                    continue;
                }

                // genera una risposta casuale per ogni domanda e calcola lo score
                $answers = [];
                $score   = 0;

                foreach ($quizQuestions as $question) {
                    $userAnswer = fake()->randomElement([0, 1]);
                    $answers[$question->id] = $userAnswer;

                    if ($userAnswer === (int) $question->is_true) {
                        $score++;
                    }
                }

                QuizAttempt::create([
                    'user_id'         => $user->id,
                    'quiz_id'         => $quiz->id,
                    'score'           => $score,
                    'total_questions' => $totalQuestions,
                    'duration'        => rand(60, $quiz->time_limit),
                    'answers'         => $answers,
                    'created_at'      => now()->subDays(rand(0, 30)),
                ]);
            }
        }

        $this->command->info('CREATI TENTATIVI QUIZ CON RISPOSTE E SCORE COERENTI');
    }
}
