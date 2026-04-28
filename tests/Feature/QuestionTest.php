<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;

class QuestionTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_question()
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $category = Category::factory()->create();

        $response = $this->actingAs($admin)->post(route('admin.questions.store'), [
            'category_id' => $category->id,
            'question' => 'Test domanda',
            'is_true' => 1
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('questions', [
            'question' => 'Test domanda'
        ]);
    }

    public function test_guest_cannot_create_question()
    {
        $category = Category::factory()->create();

        $response = $this->post(route('admin.questions.store'), [
            'category_id' => $category->id,
            'question' => 'Test',
            'is_true' => 1
        ]);

        $response->assertRedirect('/login');
    }
}
