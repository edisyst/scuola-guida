<?php

namespace Tests\Feature;

use App\Services\AuditLogService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Maatwebsite\Excel\Facades\Excel;
use Tests\TestCase;
use App\Models\Question;
use App\Models\Category;
use App\Models\AuditLog;
use App\Models\User;

class AuditLogTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(\App\Http\Middleware\EnsureTwoFactorAuthenticated::class);
    }

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

        $this->withSession(['2fa_verified' => true])->post(route('admin.questions.store'), [
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

        $this->withSession(['2fa_verified' => true])->put(route('admin.questions.update', $question), [
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

        $this->withSession(['2fa_verified' => true])->delete(route('admin.questions.destroy', $question));

        $this->assertDatabaseHas('audit_logs', [
            'event' => 'deleted',
            'model_id' => $question->id,
        ]);
    }

    public function test_only_admin_can_access_audit_logs()
    {
        $this->actingAs($this->editorUser());

        $response = $this->withSession(['2fa_verified' => true])->get(route('admin.audit.index'));

        $response->assertStatus(403);
    }

    public function test_admin_can_access_audit_logs()
    {
        $this->actingAs($this->adminUser());

        $response = $this->withSession(['2fa_verified' => true])->get(route('admin.audit.index'));

        $response->assertStatus(200);
    }

    public function test_audit_log_stores_correct_user()
    {
        $user = $this->adminUser();

        $this->actingAs($user);

        $category = Category::factory()->create();

        $this->withSession(['2fa_verified' => true])->post(route('admin.questions.store'), [
            'category_id' => $category->id,
            'question' => 'Test audit user',
            'is_true' => true,
        ]);

        $log = AuditLog::latest()->first();

        $this->assertEquals($user->id, $log->user_id);
    }

    public function test_filter_by_user_returns_only_that_user_logs()
    {
        $admin  = $this->adminUser();
        $editor = $this->editorUser();

        AuditLog::create(['user_id' => $admin->id,  'event' => 'created', 'model_type' => 'App\\Models\\Question', 'model_id' => 1, 'old_values' => null, 'new_values' => ['question' => 'A']]);
        AuditLog::create(['user_id' => $editor->id, 'event' => 'updated', 'model_type' => 'App\\Models\\Question', 'model_id' => 2, 'old_values' => ['question' => 'B'], 'new_values' => ['question' => 'C']]);

        $this->actingAs($admin);

        $response = $this->withSession(['2fa_verified' => true])
            ->get(route('admin.audit.index', ['user_id' => $admin->id]));

        $response->assertStatus(200);

        $logs = $response->viewData('logs');
        $this->assertTrue($logs->every(fn ($log) => $log->user_id === $admin->id));
        $this->assertFalse($logs->contains('user_id', $editor->id));
    }

    public function test_filter_by_event_returns_correct_rows()
    {
        $admin = $this->adminUser();

        AuditLog::create(['user_id' => $admin->id, 'event' => 'created', 'model_type' => 'App\\Models\\Question', 'model_id' => 1, 'old_values' => null, 'new_values' => ['question' => 'A']]);
        AuditLog::create(['user_id' => $admin->id, 'event' => 'deleted', 'model_type' => 'App\\Models\\Question', 'model_id' => 2, 'old_values' => ['question' => 'B'], 'new_values' => null]);

        $this->actingAs($admin);

        $response = $this->withSession(['2fa_verified' => true])
            ->get(route('admin.audit.index', ['event' => 'created']));

        $response->assertStatus(200);

        $logs = $response->viewData('logs');
        $this->assertTrue($logs->every(fn ($log) => $log->event === 'created'));
    }

    public function test_filter_by_date_range_returns_correct_rows()
    {
        $admin = $this->adminUser();

        $old = AuditLog::create(['user_id' => $admin->id, 'event' => 'created', 'model_type' => 'App\\Models\\Question', 'model_id' => 1, 'old_values' => null, 'new_values' => ['question' => 'A']]);
        $old->timestamps = false;
        $old->forceFill(['created_at' => now()->subDays(10)])->save();

        AuditLog::create(['user_id' => $admin->id, 'event' => 'created', 'model_type' => 'App\\Models\\Question', 'model_id' => 2, 'old_values' => null, 'new_values' => ['question' => 'B']]);

        $this->actingAs($admin);

        $response = $this->withSession(['2fa_verified' => true])
            ->get(route('admin.audit.index', [
                'from' => now()->subDays(3)->toDateString(),
                'to'   => now()->toDateString(),
            ]));

        $response->assertStatus(200);

        $logs = $response->viewData('logs');
        $this->assertCount(1, $logs);
        $this->assertEquals(2, $logs->first()->model_id);
    }

    public function test_get_diff_returns_correct_fields_for_update()
    {
        $service = app(AuditLogService::class);

        $log = new AuditLog([
            'event'      => 'updated',
            'model_type' => 'App\\Models\\Question',
            'model_id'   => 1,
            'old_values' => ['question' => 'Testo originale', 'is_true' => true],
            'new_values' => ['question' => 'Testo modificato', 'is_true' => false],
        ]);

        $diff = $service->getDiff($log);

        $this->assertCount(2, $diff);

        $questionDiff = collect($diff)->firstWhere('field', 'question');
        $this->assertEquals('Testo originale', $questionDiff['old']);
        $this->assertEquals('Testo modificato', $questionDiff['new']);
        $this->assertEquals('Testo domanda', $questionDiff['label']);
    }

    public function test_anonymized_user_displayed_correctly()
    {
        $admin = $this->adminUser();
        $anonUser = User::factory()->create([
            'role'  => 'viewer',
            'name'  => 'Utente Anonimo 99',
            'email' => 'anonimo-99@eliminato.invalid',
        ]);

        $log = AuditLog::create([
            'user_id'    => $anonUser->id,
            'event'      => 'created',
            'model_type' => 'App\\Models\\Question',
            'model_id'   => 1,
            'old_values' => null,
            'new_values' => ['question' => 'Test'],
        ]);

        $service = app(AuditLogService::class);
        $log->load('user');

        $this->assertEquals('Utente anonimizzato', $service->formatUser($log));
    }

    public function test_null_user_id_displayed_as_sistema()
    {
        $log = new AuditLog([
            'user_id'    => null,
            'event'      => 'created',
            'model_type' => 'App\\Models\\Question',
            'model_id'   => 1,
            'old_values' => null,
            'new_values' => ['question' => 'Test'],
        ]);

        $service = app(AuditLogService::class);

        $this->assertEquals('Sistema', $service->formatUser($log));
    }

    public function test_export_respects_active_filters()
    {
        Excel::fake();

        $admin  = $this->adminUser();
        $editor = $this->editorUser();

        AuditLog::create(['user_id' => $admin->id,  'event' => 'created', 'model_type' => 'App\\Models\\Question', 'model_id' => 1, 'old_values' => null, 'new_values' => ['question' => 'A']]);
        AuditLog::create(['user_id' => $editor->id, 'event' => 'updated', 'model_type' => 'App\\Models\\Question', 'model_id' => 2, 'old_values' => ['question' => 'B'], 'new_values' => ['question' => 'C']]);

        $this->actingAs($admin);

        $response = $this->withSession(['2fa_verified' => true])
            ->get(route('admin.audit.export', ['event' => 'created']));

        $response->assertStatus(200);
        Excel::assertDownloaded('audit-log-' . now()->format('Y-m-d') . '.xlsx');
    }

    public function test_editor_cannot_access_audit_log_show()
    {
        $editor = $this->editorUser();
        $admin  = $this->adminUser();

        $log = AuditLog::create([
            'user_id'    => $admin->id,
            'event'      => 'created',
            'model_type' => 'App\\Models\\Question',
            'model_id'   => 1,
            'old_values' => null,
            'new_values' => ['question' => 'Test'],
        ]);

        $this->actingAs($editor);

        $response = $this->withSession(['2fa_verified' => true])
            ->get(route('admin.audit.show', $log));

        $response->assertStatus(403);
    }
}
