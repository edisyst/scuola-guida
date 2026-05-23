<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\CategoryMaterial;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class CategoryMaterialFactory extends Factory
{
    protected $model = CategoryMaterial::class;

    public function definition(): array
    {
        return [
            'category_id'  => Category::factory(),
            'type'         => 'note',
            'title'        => $this->faker->sentence(4),
            'url_or_path'  => null,
            'content'      => $this->faker->paragraph(),
            'position'     => 0,
            'created_by'   => null,
        ];
    }

    public function pdf(): static
    {
        return $this->state(['type' => 'pdf', 'url_or_path' => 'materials/1/test.pdf', 'content' => null]);
    }

    public function link(): static
    {
        return $this->state(['type' => 'link', 'url_or_path' => 'https://example.com', 'content' => null]);
    }

    public function youtube(): static
    {
        return $this->state(['type' => 'link', 'url_or_path' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ', 'content' => null]);
    }
}
