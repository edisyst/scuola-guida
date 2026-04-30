<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class QuizFactory extends Factory
{
    public function definition(): array
    {
        return [
            'is_active' => $this->faker->boolean(80),
            'created_at' => now()->subDays(rand(0, 30)), // utile per ordinamento
        ];
    }
}
