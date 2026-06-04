<?php

namespace Database\Factories;

use App\Models\Question;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\QuestionTranslation>
 */
class QuestionTranslationFactory extends Factory
{
    public function definition(): array
    {
        return [
            'question_id' => Question::factory(),
            'locale'      => $this->faker->randomElement(['en', 'fr', 'de', 'es']),
            'text'        => $this->faker->sentence(10),
            'created_by'  => null,
        ];
    }

    public function locale(string $locale): static
    {
        return $this->state(fn () => ['locale' => $locale]);
    }
}
