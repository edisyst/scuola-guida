<?php

namespace Database\Factories;

use App\Models\Quiz;
use Illuminate\Database\Eloquent\Factories\Factory;

class QuizFactory extends Factory
{
    public function definition(): array
    {
        $maxQuestions = $this->faker->randomElement([10, 20, 30]);

        return [
            'title'         => ucfirst($this->faker->words(rand(2, 4), true)),
            'status'        => $this->faker->randomElement([Quiz::STATUS_DRAFT, Quiz::STATUS_PUBLISHED]),
            'max_questions' => $maxQuestions,
            'time_limit'    => $maxQuestions * 60,
            'max_errors'    => $this->faker->randomElement([3, 5]),
            'created_at'    => now()->subDays(rand(0, 30)),
        ];
    }
}
