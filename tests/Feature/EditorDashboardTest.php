<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Category;
use App\Models\Question;
use App\Models\QuestionReport;
use App\Models\Quiz;
use App\Models\User;
use App\Services\EditorMetricsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class EditorDashboardTest extends TestCase
{
    use RefreshDatabase;

    private EditorMetricsService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(\App\Http\Middleware\EnsureTwoFactorAuthenticated::class);
        $this->service = app(EditorMetricsService::class);
        Cache::flush();
    }

    // ─────────────────────────────────────────────────────────────────────────
    // SERVICE: getProductionMetrics
    // ─────────────────────────────────────────────────────────────────────────

    public function test_counts_questions_created_by_editor(): void
    {
        $editor = User::factory()->create(['role' => User::ROLE_EDITOR]);
        $other  = User::factory()->create(['role' => User::ROLE_EDITOR]);

        $from = now()->startOfMonth();
        $to   = now()->endOfMonth();

        $this->actingAs($editor);
        Question::factory()->count(3)->create();

        $this->actingAs($other);
        Question::factory()->count(2)->create();

        $metrics = $this->service->getProductionMetrics($editor, $from, $to);

        $this->assertSame(3, $metrics['questions_created']);
    }

    public function test_counts_questions_updated_by_editor(): void
    {
        $editor = User::factory()->create(['role' => User::ROLE_EDITOR]);

        $from = now()->startOfMonth();
        $to   = now()->endOfMonth();

        // Create a question as another user so the creation is not attributed to editor
        $question = Question::factory()->create();

        $this->actingAs($editor);
        $question->update(['is_true' => !$question->is_true]);
        $question->update(['is_true' => !$question->is_true]);

        $metrics = $this->service->getProductionMetrics($editor, $from, $to);

        $this->assertSame(2, $metrics['questions_updated']);
        $this->assertSame(0, $metrics['questions_created']);
    }

    public function test_counts_quizzes_published_via_audit_log(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $from = now()->startOfMonth();
        $to   = now()->endOfMonth();

        $this->actingAs($admin);

        $quiz = Quiz::factory()->create(['status' => Quiz::STATUS_DRAFT]);
        $quiz->update(['status' => Quiz::STATUS_PUBLISHED]);

        $metrics = $this->service->getProductionMetrics($admin, $from, $to);

        $this->assertSame(1, $metrics['quizzes_published']);
    }

    public function test_does_not_count_quiz_created_directly_as_published(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $from = now()->startOfMonth();
        $to   = now()->endOfMonth();

        $this->actingAs($admin);

        // Direct creation with published status is NOT a transition
        Quiz::factory()->create(['status' => Quiz::STATUS_PUBLISHED]);

        $metrics = $this->service->getProductionMetrics($admin, $from, $to);

        $this->assertSame(0, $metrics['quizzes_published']);
    }

    public function test_counts_quizzes_confirmed_via_confirmed_by_field(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $from = now()->startOfMonth();
        $to   = now()->endOfMonth();

        Quiz::factory()->count(2)->create([
            'status'       => Quiz::STATUS_CONFIRMED,
            'confirmed_by' => $admin->id,
            'confirmed_at' => now(),
        ]);

        // Quiz confirmed by another user — should not count
        $other = User::factory()->create(['role' => User::ROLE_ADMIN]);
        Quiz::factory()->create([
            'status'       => Quiz::STATUS_CONFIRMED,
            'confirmed_by' => $other->id,
            'confirmed_at' => now(),
        ]);

        $metrics = $this->service->getProductionMetrics($admin, $from, $to);

        $this->assertSame(2, $metrics['quizzes_confirmed']);
    }

    public function test_confirmed_outside_period_not_counted(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $from = now()->startOfMonth();
        $to   = now()->endOfMonth();

        Quiz::factory()->create([
            'status'       => Quiz::STATUS_CONFIRMED,
            'confirmed_by' => $admin->id,
            'confirmed_at' => now()->subMonths(2),
        ]);

        $metrics = $this->service->getProductionMetrics($admin, $from, $to);

        $this->assertSame(0, $metrics['quizzes_confirmed']);
    }

    public function test_activity_by_day_has_entry_for_each_day_in_period(): void
    {
        $editor = User::factory()->create(['role' => User::ROLE_EDITOR]);

        $from = Carbon::today()->startOfDay();
        $to   = Carbon::today()->endOfDay();

        $this->actingAs($editor);
        Question::factory()->count(2)->create();

        $metrics = $this->service->getProductionMetrics($editor, $from, $to);

        $this->assertCount(1, $metrics['activity_by_day']);
        $this->assertSame(2, $metrics['activity_by_day'][0]['total']);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // SERVICE: getGlobalContentMetrics
    // ─────────────────────────────────────────────────────────────────────────

    public function test_categories_ordered_by_question_count_desc(): void
    {
        $catMany = Category::factory()->create(['name' => 'Many']);
        $catFew  = Category::factory()->create(['name' => 'Few']);

        Question::factory()->count(5)->create(['category_id' => $catMany->id]);
        Question::factory()->count(2)->create(['category_id' => $catFew->id]);

        $metrics = $this->service->getGlobalContentMetrics();
        $cats    = $metrics['categories_by_question_count'];

        $this->assertEquals($catMany->id, $cats->first()->id);
        $this->assertEquals(5, $cats->first()->questions_count);
        $this->assertEquals(2, $cats->skip(1)->first()->questions_count);
    }

    public function test_most_reported_questions_ordered_by_pending_reports_desc(): void
    {
        $viewer = User::factory()->create(['role' => User::ROLE_VIEWER]);
        $q1     = Question::factory()->create();
        $q2     = Question::factory()->create();

        QuestionReport::factory()->count(3)->create([
            'question_id' => $q2->id,
            'user_id'     => $viewer->id,
            'status'      => 'pending',
        ]);
        QuestionReport::factory()->count(1)->create([
            'question_id' => $q1->id,
            'user_id'     => $viewer->id,
            'status'      => 'pending',
        ]);

        $metrics = $this->service->getGlobalContentMetrics();
        $reported = $metrics['most_reported_questions'];

        $this->assertEquals($q2->id, $reported->first()->id);
        $this->assertSame(3, (int) $reported->first()->pending_reports_count);
    }

    public function test_accepted_reports_not_counted_as_pending(): void
    {
        $viewer = User::factory()->create(['role' => User::ROLE_VIEWER]);
        $q      = Question::factory()->create();

        QuestionReport::factory()->count(2)->create([
            'question_id' => $q->id,
            'user_id'     => $viewer->id,
            'status'      => 'accepted',
        ]);

        $metrics  = $this->service->getGlobalContentMetrics();
        $reported = $metrics['most_reported_questions'];

        $this->assertTrue($reported->isEmpty());
    }

    public function test_quizzes_by_state_returns_correct_counts(): void
    {
        Quiz::factory()->count(2)->create(['status' => Quiz::STATUS_DRAFT]);
        Quiz::factory()->count(3)->create(['status' => Quiz::STATUS_PUBLISHED]);

        $metrics = $this->service->getGlobalContentMetrics();

        $this->assertSame(2, (int) ($metrics['quizzes_by_state'][Quiz::STATUS_DRAFT] ?? 0));
        $this->assertSame(3, (int) ($metrics['quizzes_by_state'][Quiz::STATUS_PUBLISHED] ?? 0));
    }

    // ─────────────────────────────────────────────────────────────────────────
    // SERVICE: cache
    // ─────────────────────────────────────────────────────────────────────────

    public function test_past_period_metrics_are_cached(): void
    {
        $editor = User::factory()->create(['role' => User::ROLE_EDITOR]);

        $from = now()->subMonths(2)->startOfMonth();
        $to   = now()->subMonths(2)->endOfMonth(); // past period

        $this->service->getProductionMetrics($editor, $from, $to);

        $key = "editor_metrics_{$editor->id}_{$from->format('Ymd')}_{$to->format('Ymd')}";
        $this->assertTrue(Cache::has($key));
    }

    public function test_cached_past_period_not_updated_by_new_data(): void
    {
        $editor = User::factory()->create(['role' => User::ROLE_EDITOR]);

        $from = now()->subMonths(2)->startOfMonth();
        $to   = now()->subMonths(2)->endOfMonth();

        // First call: no data → 0 created
        $first = $this->service->getProductionMetrics($editor, $from, $to);
        $this->assertSame(0, $first['questions_created']);

        // Manually inject a stale audit entry into the DB (bypassing cache)
        AuditLog::create([
            'user_id'    => $editor->id,
            'event'      => 'created',
            'model_type' => Question::class,
            'model_id'   => 999,
            'old_values' => null,
            'new_values' => ['question' => 'test'],
            'created_at' => $from->copy()->addDay(),
        ]);

        // Second call: should return cached result (still 0)
        $second = $this->service->getProductionMetrics($editor, $from, $to);
        $this->assertSame(0, $second['questions_created']);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // HTTP: access control
    // ─────────────────────────────────────────────────────────────────────────

    public function test_editor_can_access_own_dashboard(): void
    {
        $editor = User::factory()->create(['role' => User::ROLE_EDITOR]);

        $response = $this->actingAs($editor)->get(route('editor.dashboard'));

        $response->assertOk();
        $response->assertViewIs('editor.dashboard');
        $response->assertViewHas('editor', fn ($e) => $e !== null && $e->id === $editor->id);
    }

    public function test_viewer_cannot_access_editor_dashboard(): void
    {
        $viewer = User::factory()->create(['role' => User::ROLE_VIEWER]);

        $response = $this->actingAs($viewer)->get(route('editor.dashboard'));

        $response->assertForbidden();
    }

    public function test_admin_can_access_editor_dashboard(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $response = $this->actingAs($admin)->get(route('editor.dashboard'));

        $response->assertOk();
    }

    public function test_admin_can_select_specific_editor(): void
    {
        $admin  = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $editor = User::factory()->create(['role' => User::ROLE_EDITOR]);

        $response = $this->actingAs($admin)->get(
            route('editor.dashboard', ['editor_id' => $editor->id])
        );

        $response->assertOk();
        $response->assertViewHas('editor', fn ($e) => $e !== null && $e->id === $editor->id);
        $response->assertViewHas('selectedEditorId', $editor->id);
    }

    public function test_admin_without_editor_selection_sees_aggregate(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $response = $this->actingAs($admin)->get(route('editor.dashboard'));

        $response->assertOk();
        $response->assertViewHas('editor', null);
    }

    public function test_unauthenticated_user_is_redirected(): void
    {
        $response = $this->get(route('editor.dashboard'));

        $response->assertRedirect(route('login'));
    }
}
