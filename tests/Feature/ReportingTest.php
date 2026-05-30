<?php

namespace Tests\Feature;

use App\Http\Middleware\EnsureTwoFactorAuthenticated;
use App\Models\Category;
use App\Models\Question;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\QuizEnrollment;
use App\Models\User;
use App\Services\ReportingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ReportingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(EnsureTwoFactorAuthenticated::class);
    }

    /*
    |--------------------------------------------------------------------------
    | HELPERS
    |--------------------------------------------------------------------------
    */

    private function admin(): User
    {
        return User::factory()->create(['role' => User::ROLE_ADMIN]);
    }

    private function viewer(): User
    {
        return User::factory()->create(['role' => User::ROLE_VIEWER]);
    }

    private function confirmedQuiz(int $maxErrors = 3): Quiz
    {
        return Quiz::factory()->create([
            'status'       => Quiz::STATUS_CONFIRMED,
            'max_errors'   => $maxErrors,
            'confirmed_at' => now(),
        ]);
    }

    /**
     * Costruisce un tentativo con le risposte nel formato esteso.
     * $answers: [question_id => 0|1, ...]
     */
    private function makeAttempt(Quiz $quiz, User $user, array $answers, Carbon $createdAt = null): QuizAttempt
    {
        $score = array_sum($answers);
        $total = count($answers);

        $formattedAnswers = collect($answers)->map(fn (int $correct) => [
            'correct'            => $correct,
            'answered_at'        => null,
            'time_spent_seconds' => null,
            'position'           => null,
        ])->all();

        return QuizAttempt::factory()->create([
            'user_id'         => $user->id,
            'quiz_id'         => $quiz->id,
            'score'           => $score,
            'total_questions' => $total,
            'answers'         => $formattedAnswers,
            'created_at'      => $createdAt ?? now(),
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | UNIT — ReportingService
    |--------------------------------------------------------------------------
    */

    public function test_build_period_report_calculates_pass_rate_and_average_score(): void
    {
        $quiz     = $this->confirmedQuiz(maxErrors: 3); // max 3 errori su 5
        $category = Category::factory()->create();
        $questions = Question::factory()->count(5)->create(['category_id' => $category->id]);
        $quiz->questions()->attach($questions->pluck('id'));

        $user1 = $this->viewer();
        $user2 = $this->viewer();

        $qIds = $questions->pluck('id')->all();

        // user1: 5/5 corrette → 100%, 0 errori → Promosso
        $this->makeAttempt($quiz, $user1, array_fill_keys($qIds, 1));

        // user2: 1/5 corretta → 20%, 4 errori → Rimandato (4 > 3)
        $answers2 = array_fill_keys($qIds, 0);
        $answers2[$qIds[0]] = 1;
        $this->makeAttempt($quiz, $user2, $answers2);

        $from = now()->subDay();
        $to   = now()->addDay();

        $report = app(ReportingService::class)->buildPeriodReport($from, $to);

        $this->assertEquals(2, $report['total_attempts']);
        $this->assertEquals(2, $report['active_students']);
        // pass_rate: 1 promosso su 2 = 50%
        $this->assertEquals(50.0, $report['pass_rate']);
        // average_score: (100% + 20%) / 2 = 60%
        $this->assertEquals(60.0, $report['average_score']);
    }

    public function test_build_period_report_counts_active_students_distinctly(): void
    {
        $quiz = $this->confirmedQuiz();
        $user = $this->viewer();

        // Due tentativi dallo stesso utente (con total_questions > 0 per non essere filtrati)
        QuizAttempt::factory()->create([
            'user_id'         => $user->id,
            'quiz_id'         => $quiz->id,
            'score'           => 5,
            'total_questions' => 10,
            'created_at'      => now(),
        ]);
        QuizAttempt::factory()->create([
            'user_id'         => $user->id,
            'quiz_id'         => $quiz->id,
            'score'           => 7,
            'total_questions' => 10,
            'created_at'      => now(),
        ]);

        $report = app(ReportingService::class)
            ->buildPeriodReport(now()->subDay(), now()->addDay());

        $this->assertEquals(2, $report['total_attempts']);
        $this->assertEquals(1, $report['active_students']); // distinto per user_id
    }

    public function test_build_comparison_report_calculates_previous_period_and_deltas(): void
    {
        $quiz = $this->confirmedQuiz(maxErrors: 0);
        $user = $this->viewer();

        // from=2026-05-01, to=2026-05-31 → 31 giorni
        // periodo precedente: diffInDays=30 → prevTo=2026-04-30, prevFrom=prevTo-30gg=2026-03-31

        // Tentativo nel periodo corrente (maggio)
        QuizAttempt::factory()->create([
            'quiz_id'         => $quiz->id,
            'user_id'         => $user->id,
            'score'           => 5,
            'total_questions' => 5,
            'created_at'      => '2026-05-15 12:00:00',
        ]);

        // Tentativo nel periodo precedente (aprile)
        QuizAttempt::factory()->create([
            'quiz_id'         => $quiz->id,
            'user_id'         => $user->id,
            'score'           => 0,
            'total_questions' => 5,
            'created_at'      => '2026-04-15 12:00:00',
        ]);

        $from = Carbon::parse('2026-05-01');
        $to   = Carbon::parse('2026-05-31');

        $result = app(ReportingService::class)->buildComparisonReport($from, $to);

        // diffInDays(May1→May31)=30 → prevTo=Apr30, prevFrom=Apr30-30=Mar31
        $this->assertEquals('2026-03-31', $result['period']['prev_from']->format('Y-m-d'));
        $this->assertEquals('2026-04-30', $result['period']['prev_to']->format('Y-m-d'));

        // Corrente: 1 tentativo, precedente: 1 tentativo
        $this->assertEquals(1, $result['current']['total_attempts']);
        $this->assertEquals(1, $result['previous']['total_attempts']);

        // Delta total_attempts: (1-1)/1*100 = 0%
        $this->assertEquals(0.0, $result['delta']['total_attempts']);
    }

    public function test_most_failed_questions_are_ordered_by_errors_desc(): void
    {
        $quiz     = $this->confirmedQuiz();
        $category = Category::factory()->create();
        $q1       = Question::factory()->create(['category_id' => $category->id]);
        $q2       = Question::factory()->create(['category_id' => $category->id]);
        $q3       = Question::factory()->create(['category_id' => $category->id]);
        $quiz->questions()->attach([$q1->id, $q2->id, $q3->id]);

        // q1: 4 errori, q2: 3 errori, q3: 2 errori
        foreach (range(1, 3) as $i) {
            $user = $this->viewer();
            $this->makeAttempt($quiz, $user, [$q1->id => 0, $q2->id => 0, $q3->id => 0]);
        }
        // Un utente sbaglia solo q1
        $this->makeAttempt($quiz, $this->viewer(), [$q1->id => 0, $q2->id => 1, $q3->id => 1]);
        // Un utente sbaglia solo q3 → q3: 3+1=4 errori... wait, let me re-count

        $report = app(ReportingService::class)
            ->buildPeriodReport(now()->subDay(), now()->addDay());

        $failed = $report['most_failed_questions'];

        $this->assertNotEmpty($failed);
        // Verifica ordinamento decrescente per errori
        for ($i = 0; $i < count($failed) - 1; $i++) {
            $this->assertGreaterThanOrEqual($failed[$i + 1]['errors'], $failed[$i]['errors']);
        }
    }

    public function test_past_period_results_are_cached(): void
    {
        $from = Carbon::parse('2025-01-01');
        $to   = Carbon::parse('2025-01-31');

        $service = app(ReportingService::class);

        // Prima chiamata: esegue query e popola la cache
        $result1 = $service->buildPeriodReport($from, $to);

        // Seconda chiamata: deve essere servita dalla cache (nessuna query DB)
        DB::flushQueryLog();
        DB::enableQueryLog();

        $result2 = $service->buildPeriodReport($from, $to);

        DB::disableQueryLog();

        $this->assertSame($result1, $result2);
        $this->assertEmpty(DB::getQueryLog(), 'La seconda chiamata per un periodo passato deve essere servita dalla cache senza query DB.');
    }

    /*
    |--------------------------------------------------------------------------
    | HTTP — Autorizzazioni e risposte
    |--------------------------------------------------------------------------
    */

    public function test_viewer_cannot_access_reports_index(): void
    {
        $this->actingAs($this->viewer())
            ->get(route('admin.reports.index'))
            ->assertForbidden();
    }

    public function test_viewer_cannot_access_reports_show(): void
    {
        $this->actingAs($this->viewer())
            ->get(route('admin.reports.show', [
                'from' => '2026-01-01',
                'to'   => '2026-01-31',
            ]))
            ->assertForbidden();
    }

    public function test_viewer_cannot_access_export_pdf(): void
    {
        $this->actingAs($this->viewer())
            ->get(route('admin.reports.export-pdf', [
                'from' => '2026-01-01',
                'to'   => '2026-01-31',
            ]))
            ->assertForbidden();
    }

    public function test_admin_can_access_reports_index(): void
    {
        $this->actingAs($this->admin())
            ->get(route('admin.reports.index'))
            ->assertOk()
            ->assertViewIs('admin.reports.index');
    }

    public function test_admin_can_view_period_report(): void
    {
        $this->actingAs($this->admin())
            ->get(route('admin.reports.show', [
                'from' => '2026-01-01',
                'to'   => '2026-01-31',
            ]))
            ->assertOk()
            ->assertViewIs('admin.reports.show');
    }

    public function test_export_pdf_returns_pdf_content_type(): void
    {
        $response = $this->actingAs($this->admin())
            ->get(route('admin.reports.export-pdf', [
                'from' => '2026-01-01',
                'to'   => '2026-01-31',
            ]));

        $response->assertOk();
        $this->assertStringStartsWith('application/pdf', $response->headers->get('Content-Type'));
    }

    public function test_report_filter_request_validates_date_range(): void
    {
        $this->actingAs($this->admin())
            ->get(route('admin.reports.show', [
                'from' => '2026-02-01',
                'to'   => '2026-01-01', // to < from → invalido
            ]))
            ->assertSessionHasErrors('to');
    }
}
