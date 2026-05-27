<?php

namespace Database\Factories;

use App\Models\Question;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class QuestionReviewFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id'          => User::factory(),
            'question_id'      => Question::factory(),
            'next_review_at'   => $this->faker->dateTimeBetween('-1 week', '+1 week'),
            'interval_days'    => $this->faker->numberBetween(1, 30),
            'ease_factor'      => $this->faker->randomFloat(2, 1.30, 2.80),
            'repetitions'      => $this->faker->numberBetween(0, 10),
            'last_reviewed_at' => $this->faker->optional()->dateTimeBetween('-1 month', 'now'),
        ];
    }

    public function due(): static
    {
        return $this->state(['next_review_at' => now()->subHour()]);
    }

    public function future(): static
    {
        return $this->state(['next_review_at' => now()->addDays(5)]);
    }
}
