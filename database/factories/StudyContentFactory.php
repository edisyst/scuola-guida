<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\DrivingModule;
use App\Models\StudyContent;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StudyContent>
 */
class StudyContentFactory extends Factory
{
    public function definition(): array
    {
        $studyableClass = $this->faker->randomElement([Category::class, DrivingModule::class]);

        if ($studyableClass === Category::class) {
            $studyable = Category::where('is_eu_directive', true)->inRandomOrder()->first()
                ?? Category::factory()->create(['is_eu_directive' => true]);
        } else {
            $studyable = DrivingModule::inRandomOrder()->first()
                ?? DrivingModule::factory()->create();
        }

        return [
            'studyable_type' => $studyableClass,
            'studyable_id'   => $studyable->id,
            'title'          => $this->faker->sentence(4),
            'body'           => '<p>' . implode('</p><p>', $this->faker->paragraphs(3)) . '</p>',
            'is_published'   => $this->faker->boolean(),
            'order'          => $this->faker->numberBetween(0, 10),
            'created_by'     => null,
            'updated_by'     => null,
        ];
    }

    public function published(): static
    {
        return $this->state(['is_published' => true]);
    }

    public function forCategory(Category $category): static
    {
        return $this->state([
            'studyable_type' => Category::class,
            'studyable_id'   => $category->id,
        ]);
    }

    public function forModule(DrivingModule $module): static
    {
        return $this->state([
            'studyable_type' => DrivingModule::class,
            'studyable_id'   => $module->id,
        ]);
    }
}
