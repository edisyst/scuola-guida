<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\DiagnosticResult;
use App\Models\LicenseType;
use App\Models\Question;
use App\Models\QuizAttempt;
use App\Models\User;
use App\Services\StudyPlanService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class StudyPlanFeatureTest extends TestCase
{
    use RefreshDatabase;

    private LicenseType $licenseType;

    protected function setUp(): void
    {
        parent::setUp();
        $this->licenseType = LicenseType::factory()->create();
    }

    private function viewer(): User
    {
        return User::factory()->create([
            'role'                   => User::ROLE_VIEWER,
            'active_license_type_id' => $this->licenseType->id,
        ]);
    }

    private function makeAttempt(User $user, array $answersByQuestionId, array $overrides = []): QuizAttempt
    {
        $answers = [];
        foreach ($answersByQuestionId as $questionId => $correct) {
            $answers[(string) $questionId] = [
                'correct'            => $correct ? 1 : 0,
                'answered_at'        => now()->timestamp,
                'time_spent_seconds' => 5,
                'position'           => null,
            ];
        }

        return QuizAttempt::factory()->create(array_merge([
            'user_id' => $user->id,
            'answers' => $answers,
        ], $overrides));
    }

    private function makeDiagnosticResult(User $user, Category $category, bool $correct, string $batchId = null): void
    {
        DiagnosticResult::create([
            'user_id'     => $user->id,
            'category_id' => $category->id,
            'correct'     => $correct,
            'taken_at'    => now(),
            'batch_id'    => $batchId ?? (string) Str::uuid(),
        ]);
    }

    // ──────────────────────────────────────────────────────────────────────────
    // buildPlan — ordinamento per padronanza ascendente
    // ──────────────────────────────────────────────────────────────────────────

    public function test_build_plan_orders_by_mastery_ascending(): void
    {
        $viewer = $this->viewer();
        $catA   = Category::factory()->create(['name' => 'Categoria A']);
        $catB   = Category::factory()->create(['name' => 'Categoria B']);

        $qA = Question::factory()->create(['category_id' => $catA->id]);
        $qB = Question::factory()->create(['category_id' => $catB->id]);

        // catA: tutte corrette → mastery alta
        $this->makeAttempt($viewer, [$qA->id => true, $qA->id => true]);
        // catB: tutte sbagliate → mastery bassa
        $this->makeAttempt($viewer, [$qB->id => false, $qB->id => false]);

        $plan = app(StudyPlanService::class)->buildPlan($viewer);

        $this->assertTrue($plan->count() >= 2);

        $masteries = $plan->pluck('mastery')->toArray();
        $sorted    = $masteries;
        sort($sorted);

        $this->assertEquals($sorted, $masteries);
    }

    public function test_build_plan_includes_category_with_full_mastery_last(): void
    {
        $viewer = $this->viewer();
        $catWeak   = Category::factory()->create();
        $catStrong = Category::factory()->create();

        $qWeak   = Question::factory()->create(['category_id' => $catWeak->id]);
        $qStrong = Question::factory()->create(['category_id' => $catStrong->id]);

        $this->makeAttempt($viewer, [$qWeak->id => false]);
        $this->makeAttempt($viewer, [$qStrong->id => true]);

        $plan = app(StudyPlanService::class)->buildPlan($viewer);

        $weakEntry   = $plan->firstWhere(fn ($e) => $e['category']->id === $catWeak->id);
        $strongEntry = $plan->firstWhere(fn ($e) => $e['category']->id === $catStrong->id);

        $this->assertNotNull($weakEntry);
        $this->assertNotNull($strongEntry);
        $this->assertLessThan($strongEntry['mastery'], $weakEntry['mastery']);
    }

    // ──────────────────────────────────────────────────────────────────────────
    // buildPlan — utente senza dati storici (solo diagnostico)
    // ──────────────────────────────────────────────────────────────────────────

    public function test_build_plan_works_with_only_diagnostic_data(): void
    {
        $viewer   = $this->viewer();
        $category = Category::factory()->create();
        $batchId  = (string) Str::uuid();

        Question::factory()->create(['category_id' => $category->id]);
        $this->makeDiagnosticResult($viewer, $category, true, $batchId);

        $plan = app(StudyPlanService::class)->buildPlan($viewer);

        $entry = $plan->firstWhere(fn ($e) => $e['category']->id === $category->id);

        $this->assertNotNull($entry);
        $this->assertEquals(0, $entry['attempts_count']);
        $this->assertGreaterThan(0, $entry['mastery']);
    }

    public function test_build_plan_diagnostic_incorrect_gives_low_mastery(): void
    {
        $viewer   = $this->viewer();
        $category = Category::factory()->create();
        $batchId  = (string) Str::uuid();

        Question::factory()->create(['category_id' => $category->id]);
        $this->makeDiagnosticResult($viewer, $category, false, $batchId);

        $plan  = app(StudyPlanService::class)->buildPlan($viewer);
        $entry = $plan->firstWhere(fn ($e) => $e['category']->id === $category->id);

        $this->assertNotNull($entry);
        $this->assertLessThan(30, $entry['mastery']);
    }

    // ──────────────────────────────────────────────────────────────────────────
    // buildPlan — utente senza né diagnostico né dati storici
    // ──────────────────────────────────────────────────────────────────────────

    public function test_build_plan_returns_zero_mastery_for_untouched_categories(): void
    {
        $viewer   = $this->viewer();
        $category = Category::factory()->create();
        Question::factory()->create(['category_id' => $category->id]);

        $plan  = app(StudyPlanService::class)->buildPlan($viewer);
        $entry = $plan->firstWhere(fn ($e) => $e['category']->id === $category->id);

        $this->assertNotNull($entry);
        $this->assertEquals(0, $entry['mastery']);
    }

    public function test_study_plan_page_shows_empty_state_for_user_with_no_data(): void
    {
        $viewer   = $this->viewer();

        $this->actingAs($viewer)
            ->get(route('viewer.study-plan.show'))
            ->assertOk()
            ->assertSee('Inizia il tuo percorso');
    }

    // ──────────────────────────────────────────────────────────────────────────
    // buildPlan — utente con solo dati storici (nessun diagnostico)
    // ──────────────────────────────────────────────────────────────────────────

    public function test_build_plan_works_with_only_historical_data(): void
    {
        $viewer   = $this->viewer();
        $category = Category::factory()->create();
        $question = Question::factory()->create(['category_id' => $category->id]);

        $this->makeAttempt($viewer, [$question->id => true]);

        $plan  = app(StudyPlanService::class)->buildPlan($viewer);
        $entry = $plan->firstWhere(fn ($e) => $e['category']->id === $category->id);

        $this->assertNotNull($entry);
        $this->assertGreaterThan(0, $entry['attempts_count']);
        $this->assertEquals(100, $entry['mastery']);
    }

    // ──────────────────────────────────────────────────────────────────────────
    // recommended_action
    // ──────────────────────────────────────────────────────────────────────────

    public function test_recommended_action_inizia_when_mastery_below_30(): void
    {
        $viewer   = $this->viewer();
        $category = Category::factory()->create();
        $question = Question::factory()->create(['category_id' => $category->id]);

        $this->makeAttempt($viewer, [$question->id => false]);

        $plan  = app(StudyPlanService::class)->buildPlan($viewer);
        $entry = $plan->firstWhere(fn ($e) => $e['category']->id === $category->id);

        $this->assertEquals('Inizia con questa categoria', $entry['recommended_action']);
    }

    public function test_recommended_action_padronanza_when_mastery_above_70(): void
    {
        $viewer   = $this->viewer();
        $category = Category::factory()->create();
        $question = Question::factory()->create(['category_id' => $category->id]);

        // 80% correct: 4 right + 1 wrong
        $questions = Question::factory()->count(4)->create(['category_id' => $category->id]);
        foreach ($questions as $q) {
            $this->makeAttempt($viewer, [$q->id => true]);
        }
        $this->makeAttempt($viewer, [$question->id => false]);

        $plan  = app(StudyPlanService::class)->buildPlan($viewer);
        $entry = $plan->firstWhere(fn ($e) => $e['category']->id === $category->id);

        $this->assertNotNull($entry);
        $this->assertContains($entry['recommended_action'], [
            'Continua a esercitarti',
            'Padronanza buona, ripassa occasionalmente',
        ]);
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Cascata delete user
    // ──────────────────────────────────────────────────────────────────────────

    public function test_cascade_delete_removes_diagnostic_results_on_user_deletion(): void
    {
        $viewer   = $this->viewer();
        $category = Category::factory()->create();
        Question::factory()->create(['category_id' => $category->id]);

        $this->makeDiagnosticResult($viewer, $category, true);

        $this->assertDatabaseHas('diagnostic_results', ['user_id' => $viewer->id]);

        $viewer->delete();

        $this->assertDatabaseMissing('diagnostic_results', ['user_id' => $viewer->id]);
    }
}
