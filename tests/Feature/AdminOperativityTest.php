<?php

namespace Tests\Feature;

use App\Console\Commands\CloseExpiredEnrollments;
use App\Models\Question;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\QuizEnrollment;
use App\Models\User;
use App\Services\QuizSummaryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;
use Tests\TestCase;

class AdminOperativityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(\App\Http\Middleware\EnsureTwoFactorAuthenticated::class);
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
        return User::factory()->create([
            'role'                => User::ROLE_VIEWER,
            'registration_status' => User::REG_APPROVED,
            'first_name'          => 'Mario',
            'last_name'           => 'Rossi',
        ]);
    }

    private function confirmedQuiz(int $totalQuestions = 5, int $maxErrors = 1): Quiz
    {
        $quiz = Quiz::factory()->create([
            'status'        => Quiz::STATUS_CONFIRMED,
            'confirmed_at'  => now(),
            'max_questions' => $totalQuestions,
            'max_errors'    => $maxErrors,
        ]);

        Question::factory()->count($totalQuestions)->create()->each(
            fn (Question $q) => $quiz->questions()->attach($q->id)
        );

        return $quiz->refresh();
    }

    /*
    |--------------------------------------------------------------------------
    | F1 — EXPORT EXCEL
    |--------------------------------------------------------------------------
    */

    public function test_admin_can_download_xlsx_export_of_quiz_results(): void
    {
        Excel::fake();

        $admin = $this->admin();
        $quiz  = $this->confirmedQuiz();

        $response = $this->actingAs($admin)
            ->withSession(['2fa_verified' => true])
            ->get(route('admin.quizzes.export-results', $quiz));

        $response->assertOk();

        $expectedFilename = 'risultati-' . Str::slug($quiz->title)
            . '-' . now()->format('Y-m-d') . '.xlsx';

        Excel::assertDownloaded($expectedFilename);
    }

    public function test_viewer_cannot_export_quiz_results(): void
    {
        $viewer = $this->viewer();
        $quiz   = $this->confirmedQuiz();

        $this->actingAs($viewer)
            ->get(route('admin.quizzes.export-results', $quiz))
            ->assertForbidden();
    }

    /*
    |--------------------------------------------------------------------------
    | F2 — PANNELLO RIEPILOGO
    |--------------------------------------------------------------------------
    */

    public function test_summary_kpi_are_correct_with_mixed_enrollments(): void
    {
        $admin = $this->admin();
        $quiz  = $this->confirmedQuiz(totalQuestions: 10, maxErrors: 2);

        // Iscritto 1: completato e promosso (9/10, 1 errore <= 2)
        $userCompleted = User::factory()->create([
            'role'       => User::ROLE_VIEWER,
            'last_name'  => 'Bianchi',
            'first_name' => 'Anna',
        ]);
        $enrollmentCompleted = QuizEnrollment::create([
            'quiz_id'      => $quiz->id,
            'user_id'      => $userCompleted->id,
            'status'       => QuizEnrollment::STATUS_COMPLETED,
            'completed_at' => now(),
        ]);
        QuizAttempt::create([
            'user_id'            => $userCompleted->id,
            'quiz_id'            => $quiz->id,
            'quiz_enrollment_id' => $enrollmentCompleted->id,
            'score'              => 9,
            'total_questions'    => 10,
            'duration'           => 600,
        ]);

        // Iscritto 2: approvato senza tentativo (non svolto)
        $userPending = User::factory()->create([
            'role'      => User::ROLE_VIEWER,
            'last_name' => 'Verdi',
        ]);
        QuizEnrollment::create([
            'quiz_id' => $quiz->id,
            'user_id' => $userPending->id,
            'status'  => QuizEnrollment::STATUS_APPROVED,
        ]);

        // Iscritto 3: completato rimandato (6/10, 4 errori > 2)
        $userFailed = User::factory()->create([
            'role'      => User::ROLE_VIEWER,
            'last_name' => 'Neri',
        ]);
        $enrollmentFailed = QuizEnrollment::create([
            'quiz_id'      => $quiz->id,
            'user_id'      => $userFailed->id,
            'status'       => QuizEnrollment::STATUS_COMPLETED,
            'completed_at' => now(),
        ]);
        QuizAttempt::create([
            'user_id'            => $userFailed->id,
            'quiz_id'            => $quiz->id,
            'quiz_enrollment_id' => $enrollmentFailed->id,
            'score'              => 6,
            'total_questions'    => 10,
            'duration'           => 1200,
        ]);

        $summary = (new QuizSummaryService())->getSummary($quiz);

        $this->assertSame(3, $summary['kpi']['total']);
        $this->assertSame(2, $summary['kpi']['completed']);
        $this->assertSame(1, $summary['kpi']['pending']);
        // Media percentuali: (90 + 60) / 2 = 75.0
        $this->assertSame(75.0, $summary['kpi']['average_score']);

        // La view risponde 200 con l'admin
        $this->actingAs($admin)
            ->withSession(['2fa_verified' => true])
            ->get(route('admin.quizzes.summary', $quiz))
            ->assertOk()
            ->assertSee('Bianchi')
            ->assertSee('Neri')
            ->assertSee('Verdi');
    }

    public function test_viewer_cannot_access_quiz_summary(): void
    {
        $viewer = $this->viewer();
        $quiz   = $this->confirmedQuiz();

        $this->actingAs($viewer)
            ->get(route('admin.quizzes.summary', $quiz))
            ->assertForbidden();
    }

    /*
    |--------------------------------------------------------------------------
    | F3 — SCHEDULAZIONE
    |--------------------------------------------------------------------------
    */

    public function test_close_expired_command_rejects_only_pending_enrollments(): void
    {
        $this->admin(); // il comando cerca User::ROLE_ADMIN per il reviewer di sistema

        $quiz = $this->confirmedQuiz();
        $quiz->update(['enrollments_close_at' => now()->subHour()]);

        $userA = User::factory()->create(['role' => User::ROLE_VIEWER]);
        $userB = User::factory()->create(['role' => User::ROLE_VIEWER]);
        $userC = User::factory()->create(['role' => User::ROLE_VIEWER]);

        $pending = QuizEnrollment::create([
            'quiz_id' => $quiz->id,
            'user_id' => $userA->id,
            'status'  => QuizEnrollment::STATUS_PENDING,
        ]);
        $approved = QuizEnrollment::create([
            'quiz_id' => $quiz->id,
            'user_id' => $userB->id,
            'status'  => QuizEnrollment::STATUS_APPROVED,
        ]);
        $completed = QuizEnrollment::create([
            'quiz_id'      => $quiz->id,
            'user_id'      => $userC->id,
            'status'       => QuizEnrollment::STATUS_COMPLETED,
            'completed_at' => now()->subDay(),
        ]);

        Artisan::call(CloseExpiredEnrollments::class);

        $this->assertSame(QuizEnrollment::STATUS_REJECTED, $pending->fresh()->status);
        $this->assertSame(QuizEnrollment::STATUS_APPROVED, $approved->fresh()->status);
        $this->assertSame(QuizEnrollment::STATUS_COMPLETED, $completed->fresh()->status);
    }

    public function test_close_expired_command_skips_quizzes_with_future_close_date(): void
    {
        $quiz = $this->confirmedQuiz();
        $quiz->update(['enrollments_close_at' => now()->addDay()]);

        $user = User::factory()->create(['role' => User::ROLE_VIEWER]);
        $pending = QuizEnrollment::create([
            'quiz_id' => $quiz->id,
            'user_id' => $user->id,
            'status'  => QuizEnrollment::STATUS_PENDING,
        ]);

        Artisan::call(CloseExpiredEnrollments::class);

        $this->assertSame(QuizEnrollment::STATUS_PENDING, $pending->fresh()->status);
    }

    public function test_update_schedule_rejects_close_before_open(): void
    {
        $admin = $this->admin();
        $quiz  = $this->confirmedQuiz();

        $response = $this->actingAs($admin)
            ->withSession(['2fa_verified' => true])
            ->put(route('admin.quizzes.schedule.update', $quiz), [
                'enrollments_open_at'  => now()->addDay()->format('Y-m-d\TH:i'),
                'enrollments_close_at' => now()->subDay()->format('Y-m-d\TH:i'),
            ]);

        $response->assertSessionHasErrors('enrollments_close_at');

        $quiz->refresh();
        $this->assertNull($quiz->enrollments_open_at);
        $this->assertNull($quiz->enrollments_close_at);
    }

    public function test_update_schedule_accepts_valid_window(): void
    {
        $admin = $this->admin();
        $quiz  = $this->confirmedQuiz();

        $open  = now()->addDay();
        $close = now()->addDays(7);

        $response = $this->actingAs($admin)
            ->withSession(['2fa_verified' => true])
            ->put(route('admin.quizzes.schedule.update', $quiz), [
                'enrollments_open_at'  => $open->format('Y-m-d\TH:i'),
                'enrollments_close_at' => $close->format('Y-m-d\TH:i'),
            ]);

        $response->assertRedirect(route('admin.quizzes.index'));
        $quiz->refresh();
        $this->assertNotNull($quiz->enrollments_open_at);
        $this->assertNotNull($quiz->enrollments_close_at);
        $this->assertSame($open->format('Y-m-d H:i'), $quiz->enrollments_open_at->format('Y-m-d H:i'));
        $this->assertSame($close->format('Y-m-d H:i'), $quiz->enrollments_close_at->format('Y-m-d H:i'));
    }
}
