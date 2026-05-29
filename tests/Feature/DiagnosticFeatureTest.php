<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\DiagnosticResult;
use App\Models\Question;
use App\Models\QuizAttempt;
use App\Models\User;
use App\Services\DiagnosticService;
use App\Services\StudyPlanService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class DiagnosticFeatureTest extends TestCase
{
    use RefreshDatabase;

    private function viewer(): User
    {
        return User::factory()->create(['role' => User::ROLE_VIEWER]);
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => User::ROLE_ADMIN]);
    }

    // ──────────────────────────────────────────────────────────────────────────
    // ACCESSO ROUTE
    // ──────────────────────────────────────────────────────────────────────────

    public function test_diagnostic_route_requires_authentication(): void
    {
        $this->get(route('viewer.diagnostic.show'))
            ->assertRedirect(route('login'));
    }

    public function test_study_plan_route_requires_authentication(): void
    {
        $this->get(route('viewer.study-plan.show'))
            ->assertRedirect(route('login'));
    }

    public function test_admin_cannot_access_diagnostic(): void
    {
        $this->actingAs($this->admin())
            ->get(route('viewer.diagnostic.show'))
            ->assertForbidden();
    }

    public function test_admin_cannot_access_study_plan(): void
    {
        $this->actingAs($this->admin())
            ->get(route('viewer.study-plan.show'))
            ->assertForbidden();
    }

    public function test_viewer_can_access_diagnostic(): void
    {
        $this->actingAs($this->viewer())
            ->get(route('viewer.diagnostic.show'))
            ->assertOk();
    }

    public function test_viewer_can_access_study_plan(): void
    {
        $this->actingAs($this->viewer())
            ->get(route('viewer.study-plan.show'))
            ->assertOk();
    }

    // ──────────────────────────────────────────────────────────────────────────
    // DiagnosticService::generateQuestions
    // ──────────────────────────────────────────────────────────────────────────

    public function test_generate_questions_returns_one_per_category(): void
    {
        $viewer = $this->viewer();

        $cat1 = Category::factory()->create();
        $cat2 = Category::factory()->create();
        $cat3 = Category::factory()->create();

        Question::factory()->create(['category_id' => $cat1->id]);
        Question::factory()->create(['category_id' => $cat2->id]);
        Question::factory()->create(['category_id' => $cat3->id]);

        $service   = app(DiagnosticService::class);
        $questions = $service->generateQuestions($viewer);

        $this->assertCount(3, $questions);

        $categoryIds = $questions->pluck('category_id')->sort()->values()->toArray();
        $this->assertEquals(
            collect([$cat1->id, $cat2->id, $cat3->id])->sort()->values()->toArray(),
            $categoryIds
        );
    }

    public function test_generate_questions_returns_no_duplicates(): void
    {
        $viewer = $this->viewer();

        $category = Category::factory()->create();
        Question::factory()->count(5)->create(['category_id' => $category->id]);

        $service   = app(DiagnosticService::class);
        $questions = $service->generateQuestions($viewer);

        $this->assertCount(1, $questions);
        $this->assertEquals($category->id, $questions->first()->category_id);
    }

    public function test_generate_questions_skips_category_with_no_questions(): void
    {
        $viewer = $this->viewer();

        $catWithQ    = Category::factory()->create();
        $catWithoutQ = Category::factory()->create();

        Question::factory()->create(['category_id' => $catWithQ->id]);

        $service   = app(DiagnosticService::class);
        $questions = $service->generateQuestions($viewer);

        $this->assertCount(1, $questions);
        $this->assertEquals($catWithQ->id, $questions->first()->category_id);
    }

    public function test_generate_questions_excludes_recently_seen_when_alternatives_exist(): void
    {
        $viewer   = $this->viewer();
        $category = Category::factory()->create();

        $q1 = Question::factory()->create(['category_id' => $category->id]);
        $q2 = Question::factory()->create(['category_id' => $category->id]);

        // Simula un tentativo recente con q1.
        // created_at esplicito: la factory usa rand(0,30) giorni fa, il che
        // porterebbe il tentativo fuori dalla finestra di 24h di recentlySeenQuestionIds().
        QuizAttempt::factory()->create([
            'user_id'    => $viewer->id,
            'created_at' => now(),
            'answers'    => [
                (string) $q1->id => ['correct' => 1, 'answered_at' => now()->timestamp],
            ],
        ]);

        $service   = app(DiagnosticService::class);
        $questions = $service->generateQuestions($viewer);

        $this->assertCount(1, $questions);
        $this->assertEquals($q2->id, $questions->first()->id);
    }

    // ──────────────────────────────────────────────────────────────────────────
    // DiagnosticService::saveResults
    // ──────────────────────────────────────────────────────────────────────────

    public function test_save_results_persists_to_diagnostic_results(): void
    {
        $viewer   = $this->viewer();
        $category = Category::factory()->create();
        $q1       = Question::factory()->create(['category_id' => $category->id]);
        $q2       = Question::factory()->create(['category_id' => $category->id]);

        $service = app(DiagnosticService::class);
        $service->saveResults($viewer, [
            $q1->id => 1,
            $q2->id => 0,
        ]);

        $this->assertDatabaseHas('diagnostic_results', [
            'user_id'     => $viewer->id,
            'category_id' => $category->id,
            'correct'     => 1,
        ]);

        $this->assertDatabaseHas('diagnostic_results', [
            'user_id'     => $viewer->id,
            'category_id' => $category->id,
            'correct'     => 0,
        ]);
    }

    public function test_save_results_groups_by_batch_id(): void
    {
        $viewer   = $this->viewer();
        $category = Category::factory()->create();
        $q1       = Question::factory()->create(['category_id' => $category->id]);
        $q2       = Question::factory()->create(['category_id' => $category->id]);

        $service = app(DiagnosticService::class);
        $service->saveResults($viewer, [$q1->id => 1, $q2->id => 0]);

        $batchIds = DiagnosticResult::where('user_id', $viewer->id)
            ->pluck('batch_id')
            ->unique();

        $this->assertCount(1, $batchIds);
    }

    public function test_save_results_noop_when_answers_empty(): void
    {
        $viewer  = $this->viewer();
        $service = app(DiagnosticService::class);
        $service->saveResults($viewer, []);

        $this->assertDatabaseCount('diagnostic_results', 0);
    }

    // ──────────────────────────────────────────────────────────────────────────
    // DiagnosticService::hasDiagnostic
    // ──────────────────────────────────────────────────────────────────────────

    public function test_has_diagnostic_false_for_new_user(): void
    {
        $viewer  = $this->viewer();
        $service = app(DiagnosticService::class);

        $this->assertFalse($service->hasDiagnostic($viewer));
    }

    public function test_has_diagnostic_true_after_save(): void
    {
        $viewer   = $this->viewer();
        $category = Category::factory()->create();
        $question = Question::factory()->create(['category_id' => $category->id]);

        $service = app(DiagnosticService::class);
        $service->saveResults($viewer, [$question->id => 1]);

        $this->assertTrue($service->hasDiagnostic($viewer));
    }

    // ──────────────────────────────────────────────────────────────────────────
    // DiagnosticService::getLatestDiagnostic
    // ──────────────────────────────────────────────────────────────────────────

    public function test_get_latest_diagnostic_returns_null_for_new_user(): void
    {
        $viewer  = $this->viewer();
        $service = app(DiagnosticService::class);

        $this->assertNull($service->getLatestDiagnostic($viewer));
    }

    public function test_get_latest_diagnostic_returns_most_recent_batch(): void
    {
        $viewer   = $this->viewer();
        $category = Category::factory()->create();
        $q1       = Question::factory()->create(['category_id' => $category->id]);
        $q2       = Question::factory()->create(['category_id' => $category->id]);

        $oldBatch = Str::uuid();
        $newBatch = Str::uuid();

        DiagnosticResult::create([
            'user_id'     => $viewer->id,
            'category_id' => $category->id,
            'correct'     => false,
            'taken_at'    => now()->subDay(),
            'batch_id'    => $oldBatch,
        ]);

        DiagnosticResult::create([
            'user_id'     => $viewer->id,
            'category_id' => $category->id,
            'correct'     => true,
            'taken_at'    => now(),
            'batch_id'    => $newBatch,
        ]);

        $service  = app(DiagnosticService::class);
        $results  = $service->getLatestDiagnostic($viewer);

        $this->assertNotNull($results);
        $this->assertCount(1, $results);
        $this->assertEquals($newBatch, $results->first()->batch_id);
        $this->assertTrue($results->first()->correct);
    }
}
