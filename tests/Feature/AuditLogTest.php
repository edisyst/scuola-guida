<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\Question;
use App\Models\Category;
use App\Models\AuditLog;
use App\Models\User;

class AuditLogTest extends TestCase
{
    use RefreshDatabase;

    protected function adminUser()
    {
        return User::factory()->create(['role' => 'admin']);
    }

    protected function editorUser()
    {
        return User::factory()->create(['role' => 'editor']);
    }

    protected function viewerUser()
    {
        return User::factory()->create(['role' => 'viewer']);
    }

    public function test_audit_log_created_when_question_created()
    {
        $user = $this->adminUser();

        $this->actingAs($user);

        $category = Category::factory()->create();

        $this->post(route('admin.questions.store'), [
            'category_id' => $category->id,
            'question' => 'Domanda test',
            'is_true' => true,
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'event' => 'created',
            'user_id' => $user->id,
        ]);
    }

    public function test_audit_log_created_when_question_updated()
    {
        $user = $this->adminUser();
        $this->actingAs($user);

        $question = Question::factory()->create();

        $this->put(route('admin.questions.update', $question), [
            'category_id' => $question->category_id,
            'question' => 'Domanda modificata',
            'is_true' => false,
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'event' => 'updated',
            'model_id' => $question->id,
        ]);
    }

    public function test_audit_log_created_when_question_deleted()
    {
        $user = $this->adminUser();
        $this->actingAs($user);

        $question = Question::factory()->create();

        $this->delete(route('admin.questions.destroy', $question));

        $this->assertDatabaseHas('audit_logs', [
            'event' => 'deleted',
            'model_id' => $question->id,
        ]);
    }

    public function test_only_admin_can_access_audit_logs()
    {
        $this->actingAs($this->editorUser());

        $response = $this->get(route('admin.audit.index'));

        $response->assertStatus(403);
    }

    public function test_admin_can_access_audit_logs()
    {
        $this->actingAs($this->adminUser());

        $response = $this->get(route('admin.audit.index'));

        $response->assertStatus(200);
    }

    public function test_audit_log_stores_correct_user()
    {
        $user = $this->adminUser();

        $this->actingAs($user);

        $category = Category::factory()->create();

        $this->post(route('admin.questions.store'), [
            'category_id' => $category->id,
            'question' => 'Test audit user',
            'is_true' => true,
        ]);

        $log = AuditLog::latest()->first();

        $this->assertEquals($user->id, $log->user_id);
    }
}
