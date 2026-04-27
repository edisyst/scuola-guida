<?php

namespace Database\Factories;

use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Question>
 */
class QuestionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'category_id' => Category::factory(), // 🔥 IMPORTANTISSIMO
            'question' => $this->faker->sentence(10),
            'is_true' => $this->faker->boolean(),
            'image' => 'questions/images/test/' . $this->faker->numberBetween(1, 10) . '.png',
        ];
    }
}
