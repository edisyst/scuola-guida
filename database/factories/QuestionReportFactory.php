<?php

namespace Database\Factories;

use App\Models\Question;
use App\Models\QuestionReport;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\QuestionReport>
 */
class QuestionReportFactory extends Factory
{
    protected $model = QuestionReport::class;

    public function definition(): array
    {
        return [
            'question_id' => Question::factory(),
            'user_id'     => User::factory()->state(['role' => User::ROLE_VIEWER]),
            'body'        => $this->faker->sentence(12),
            'type'        => $this->faker->randomElement(array_keys(QuestionReport::types())),
            'status'      => QuestionReport::STATUS_PENDING,
            'admin_note'  => null,
            'resolved_by' => null,
            'resolved_at' => null,
        ];
    }
}
