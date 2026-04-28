<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;

class QuestionTest extends TestCase
{
    use RefreshDatabase;

    protected function adminUser()
    {
        return \App\Models\User::factory()->create([
            'role' => 'admin'
        ]);
    }

    protected function editorUser()
    {
        return \App\Models\User::factory()->create([
            'role' => 'editor'
        ]);
    }

    protected function viewerUser()
    {
        return \App\Models\User::factory()->create([
            'role' => 'viewer'
        ]);
    }

    public function test_admin_can_create_question()
    {
        $user = $this->adminUser();
        $this->actingAs($user);

        $category = \App\Models\Category::factory()->create();

        $response = $this->post(route('admin.questions.store'), [
            'category_id' => $category->id,
            'question' => 'Test domanda',
            'is_true' => true,
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('questions', [
            'question' => 'Test domanda',
        ]);
    }

// ANCORA NON IMPLEMENTATO IL BLOCCO
//     public function test_viewer_cannot_create_question()
//     {
//         $user = $this->viewerUser();
//         $this->actingAs($user);
//
//         $category = \App\Models\Category::factory()->create();
//
//         $response = $this->post(route('admin.questions.store'), [
//             'category_id' => $category->id,
//             'question' => 'Test domanda',
//             'is_true' => true,
//         ]);
//
//         $response->assertStatus(403);
//     }

    public function test_admin_can_delete_question()
    {
        $user = $this->adminUser();

        $this->actingAs($user);

        $question = \App\Models\Question::factory()->create();

        $response = $this->delete(route('admin.questions.destroy', $question));

        $response->assertRedirect();

        $this->assertDatabaseMissing('questions', [
            'id' => $question->id
        ]);
    }

// ANCORA NON IMPLEMENTATO IL BLOCCO
//     public function test_editor_cannot_delete_question()
//     {
//         $user = $this->editorUser();
//
//         $this->actingAs($user);
//
//         $question = \App\Models\Question::factory()->create();
//
//         $response = $this->delete(route('admin.questions.destroy', $question));
//
//         $response->assertStatus(403);
//     }
}
