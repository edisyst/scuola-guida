<?php

namespace Database\Factories;

use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;

class CategoryTranslationFactory extends Factory
{
    public function definition(): array
    {
        return [
            'category_id' => Category::factory(),
            'locale'      => fake()->randomElement(['en', 'fr', 'de', 'es']),
            'name'        => fake()->words(3, true),
            'created_by'  => null,
        ];
    }

    public function locale(string $locale): static
    {
        return $this->state(['locale' => $locale]);
    }
}
