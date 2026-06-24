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
                Quiz::STATUS_DRAFT     => fake()->randomElement(['Bozza', 'Quiz in preparazione', 'Bozza sessione']),
                Quiz::STATUS_PUBLISHED => fake()->randomElement([
                    'Esercitazione segnaletica', 'Quiz di prova – Patente B',
                    'Ripasso norme di comportamento', 'Esercitazione limiti di velocità',
                    'Quiz misto – teoria e segnali', 'Allenamento pre-esame',
                ]),
                Quiz::STATUS_CONFIRMED => fake()->randomElement([
                    'Simulazione esame B – sessione primaverile',
                    'Simulazione esame B – sessione estiva',
                    'Simulazione esame B – sessione autunnale',
                ]),
                default => 'Quiz',
            };

            if ($quiz->title === 'Quiz') {
                $quiz->updateQuietly(['title' => "{$prefix} #{$id}"]);
            }
        });
    }
}
