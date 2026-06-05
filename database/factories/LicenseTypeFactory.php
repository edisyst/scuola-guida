<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\LicenseType>
 */
class LicenseTypeFactory extends Factory
{
    public function definition(): array
    {
        return [
            'code' => $this->faker->unique()->bothify('?-??'),
            'name' => $this->faker->words(3, true),
            'description' => $this->faker->optional()->sentence(),
            'exam_questions' => $this->faker->optional(0.5)->numberBetween(25, 40),
            'exam_minutes' => $this->faker->optional(0.5)->numberBetween(15, 30),
            'exam_max_errors' => $this->faker->optional(0.5)->numberBetween(2, 5),
            'sort_order' => $this->faker->numberBetween(0, 100),
            'is_active' => true,
        ];
    }
}
