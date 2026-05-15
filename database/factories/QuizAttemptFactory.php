<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\User;
use App\Models\Quiz;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\QuizAttempt>
 */
class QuizAttemptFactory extends Factory
{
    public function definition(): array
    {
        $totalQuestions = $this->faker->randomElement([10, 20, 30]);
        $score          = rand(0, $totalQuestions);
        $timeLimit      = $totalQuestions * 60;

        return [
            'user_id'         => User::factory(),
            'quiz_id'         => Quiz::factory(),
            'score'           => $score,
            'total_questions' => $totalQuestions,
            'duration'        => rand(60, $timeLimit),
            'answers'         => null,
            'created_at'      => now()->subDays(rand(0, 30)),
        ];
    }
}
