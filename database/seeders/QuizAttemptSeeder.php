<?php

namespace Database\Seeders;

use App\Models\QuizEnrollment;
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

        // 3 esiti di quiz su confirmed-results
        $this->createConfirmedResults($quizzes);
    }

    private function createConfirmedResults($quizzes): void
    {
        $confirmedQuiz = $quizzes->firstWhere('status', Quiz::STATUS_CONFIRMED);

        if (!$confirmedQuiz || $confirmedQuiz->questions->isEmpty()) {
            $this->command->warn('Nessun quiz confermato con domande, salto esiti confermati');
            return;
        }

        // i 3 viewer con iscrizioni in attesa
        $viewers = User::where('role', User::ROLE_VIEWER)
            ->where('registration_status', User::REG_PENDING)
            ->limit(3)
            ->get();

        foreach ($viewers as $viewer) {
            $quizQuestions  = $confirmedQuiz->questions;
            $totalQuestions = $quizQuestions->count();

            // genera risposte
            $answers = [];
            $score   = 0;

            foreach ($quizQuestions as $question) {
                $userAnswer = fake()->randomElement([0, 1]);
                $answers[$question->id] = $userAnswer;

                if ($userAnswer === (int) $question->is_true) {
                    $score++;
                }
            }

            // crea enrollment
            $enrollment = QuizEnrollment::create([
                'quiz_id'      => $confirmedQuiz->id,
                'user_id'      => $viewer->id,
                'status'       => QuizEnrollment::STATUS_COMPLETED,
                'completed_at' => now()->subDays(rand(1, 5)),
            ]);

            // crea attempt associato all'enrollment
            QuizAttempt::create([
                'user_id'           => $viewer->id,
                'quiz_id'           => $confirmedQuiz->id,
                'quiz_enrollment_id' => $enrollment->id,
                'score'             => $score,
                'total_questions'   => $totalQuestions,
                'duration'          => rand(60, $confirmedQuiz->time_limit),
                'answers'           => $answers,
                'created_at'        => now()->subDays(rand(1, 5)),
            ]);
        }

        $this->command->info('CREATI 3 ESITI QUIZ CONFERMATI CON ENROLLMENT');
    }
}
