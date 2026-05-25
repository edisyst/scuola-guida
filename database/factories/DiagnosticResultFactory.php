<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\DiagnosticResult;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class DiagnosticResultFactory extends Factory
{
    protected $model = DiagnosticResult::class;

    public function definition(): array
    {
        return [
            'user_id'     => User::factory(),
            'category_id' => Category::factory(),
            'correct'     => $this->faker->boolean(),
            'taken_at'    => now(),
            'batch_id'    => (string) Str::uuid(),
        ];
    }
}
