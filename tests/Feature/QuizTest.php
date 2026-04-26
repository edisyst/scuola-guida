<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Question;
use Illuminate\Foundation\Testing\RefreshDatabase;

class QuizTest extends TestCase
{
    use RefreshDatabase;

    public function test_quiz_submission()
    {
        $user = User::factory()->create();

        $q = Question::factory()->create(['is_true' => true]);

        $response = $this->actingAs($user)->post(route('quiz.submit'), [
            'answers' => [
                $q->id => 1
            ]
        ]);

        $response->assertOk();

        $this->assertDatabaseHas('quiz_results', [
            'user_id' => $user->id,
            'score' => 1
        ]);
    }
}
