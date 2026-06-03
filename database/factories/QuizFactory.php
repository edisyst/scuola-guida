<?php

namespace Database\Factories;

use App\Models\Quiz;
use Illuminate\Database\Eloquent\Factories\Factory;

class QuizFactory extends Factory
{
    public function definition(): array
    {
        $maxQuestions = $this->faker->randomElement([10, 15, 20, 30]);

        return [
            'title'         => 'Quiz',
            'status'        => $this->faker->randomElement([Quiz::STATUS_DRAFT, Quiz::STATUS_PUBLISHED]),
            'max_questions' => $maxQuestions,
            'time_limit'    => $maxQuestions * 60,
            'max_errors'    => $this->faker->randomElement([3, 5]),
            'created_at'    => now()->subDays(rand(0, 30)),
        ];
    }

    public function configure(): static
    {
        return $this->afterCreating(function (Quiz $quiz) {
            $id = str_pad($quiz->id, 2, '0', STR_PAD_LEFT);

            $prefix = match ($quiz->status) {
                Quiz::STATUS_DRAFT     => 'Bozza di quiz',
                Quiz::STATUS_PUBLISHED => fake()->randomElement(['Esercitazione', 'Quiz di prova']),
                Quiz::STATUS_CONFIRMED => 'Quiz di esame',
                default                => 'Quiz',
            };

            $quiz->updateQuietly(['title' => "{$prefix} #{$id} da {$quiz->max_questions} domande"]);
        });
    }
}
