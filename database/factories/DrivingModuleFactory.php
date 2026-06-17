<?php

namespace Database\Factories;

use App\Models\LicenseType;
use Illuminate\Database\Eloquent\Factories\Factory;

class DrivingModuleFactory extends Factory
{
    public function definition(): array
    {
        return [
            'license_type_id' => LicenseType::factory(),
            'code'            => strtoupper($this->faker->lexify('??')),
            'name'            => $this->faker->sentence(3),
            'description'     => $this->faker->sentence(),
            'required_hours'  => $this->faker->randomFloat(1, 1, 4),
            'sort_order'      => $this->faker->numberBetween(1, 10),
        ];
    }
}
