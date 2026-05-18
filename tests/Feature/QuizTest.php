<?php

namespace Tests\Feature;

use App\Models\Question;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\QuizEnrollment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuizTest extends TestCase
{
    use RefreshDatabase;

    /*
    |--------------------------------------------------------------------------
    | HELPERS
    |--------------------------------------------------------------------------
    */

    /** Crea un viewer con registrazione anagrafica approvata. */
    private function approvedViewer(): User
    {
        return User::factory()->create([
            'role'                => User::ROLE_VIEWER,
            'registration_status' => User::REG_APPROVED,
        ]);
    }

    /**
     * Costruisce un quiz nello stato richiesto con N domande, restituendo
     * anche le domande (così i test possono comporre gli array `answers`).
     *
     * @return array{quiz: Quiz, questions: \Illuminate\Database\Eloquent\Collection<int, Question>}
     */
    private function quizWithQuestions(string $status, int $count = 5): array
    {
        $quiz = Quiz::factory()->create([
            'status'        => $status,
            'max_questions' => $count,
        ]);

        $questions = Question::factory()->count($count)->create();
        $quiz->questions()->attach($questions->pluck('id'));

        if ($status === Quiz::STATUS_CONFIRMED) {
            $quiz->update(['confirmed_at' => now()]);
        }

        return ['quiz' => $quiz->refresh(), 'questions' => $questions];
    }

    /*
    |--------------------------------------------------------------------------
    | TESTS — nuovo flusso /quiz/attempts
    |--------------------------------------------------------------------------
    */

    public function test_approved_viewer_can_create_attempt_on_published_quiz(): void
    {
        $viewer = $this->approvedViewer();
        ['quiz' => $quiz, 'questions' => $questions] = $this->quizWithQuestions(Quiz::STATUS_PUBLISHED, 3);

        $answers = $questions->mapWithKeys(fn (Question $q) => [
            $q->id => ['correct' => (int) $q->is_true, 'answered_at' => null, 'time_spent_seconds' => null, 'position' => null],
        ])->all();

        $response = $this->actingAs($viewer)->postJson(route('quiz.attempts.store'), [
            'quiz_id' => $quiz->id,
            'answers' => $answers,
        ]);

        $response->assertOk()
            ->assertJson(['success' => true, 'score' => 3, 'total' => 3]);

        $this->assertDatabaseHas('quiz_attempts', [
            'user_id' => $viewer->id,
            'quiz_id' => $quiz->id,
            'score'   => 3,
        ]);
    }

    public function test_approved_viewer_can_create_attempt_on_confirmed_quiz_with_enrollment(): void
    {
        $viewer = $this->approvedViewer();
        ['quiz' => $quiz, 'questions' => $questions] = $this->quizWithQuestions(Quiz::STATUS_CONFIRMED, 4);

        // Iscrizione approvata: requisito per giocare un quiz confermato.
        $enrollment = QuizEnrollment::create([
            'quiz_id' => $quiz->id,
            'user_id' => $viewer->id,
            'status'  => QuizEnrollment::STATUS_APPROVED,
        ]);

        $answers = $questions->mapWithKeys(fn (Question $q) => [
            $q->id => ['correct' => (int) $q->is_true, 'answered_at' => null, 'time_spent_seconds' => null, 'position' => null],
        ])->all();

        $response = $this->actingAs($viewer)->postJson(route('quiz.attempts.store'), [
            'quiz_id' => $quiz->id,
            'answers' => $answers,
        ]);

        $response->assertOk()
            ->assertJson(['success' => true, 'score' => 4, 'total' => 4]);

        $this->assertDatabaseHas('quiz_attempts', [
            'user_id'            => $viewer->id,
            'quiz_id'            => $quiz->id,
            'quiz_enrollment_id' => $enrollment->id,
            'score'              => 4,
        ]);

        // L'iscrizione viene consumata e marcata come completata.
        $this->assertSame(QuizEnrollment::STATUS_COMPLETED, $enrollment->fresh()->status);
    }

    /*
    |--------------------------------------------------------------------------
    | TESTS — dettaglio tentativo (show)
    |--------------------------------------------------------------------------
    */

    public function test_viewer_can_see_own_attempt_detail(): void
    {
        $viewer = $this->approvedViewer();
        ['quiz' => $quiz, 'questions' => $questions] = $this->quizWithQuestions(Quiz::STATUS_PUBLISHED, 3);

        $attempt = QuizAttempt::create([
            'user_id'         => $viewer->id,
            'quiz_id'         => $quiz->id,
            'score'           => 3,
            'total_questions' => 3,
            'answers'         => $questions->mapWithKeys(fn ($q, $i) => [
                $q->id => ['correct' => (int) $q->is_true, 'answered_at' => null, 'time_spent_seconds' => null, 'position' => $i + 1],
            ])->all(),
        ]);

        $this->actingAs($viewer)
            ->get(route('quiz.attempts.show', $attempt))
            ->assertOk()
            ->assertViewIs('quiz.attempt');
    }

    public function test_viewer_cannot_see_other_attempt_detail(): void
    {
        $owner  = $this->approvedViewer();
        $other  = $this->approvedViewer();
        ['quiz' => $quiz] = $this->quizWithQuestions(Quiz::STATUS_PUBLISHED, 2);

        $attempt = QuizAttempt::create([
            'user_id'         => $owner->id,
            'quiz_id'         => $quiz->id,
            'score'           => 1,
            'total_questions' => 2,
            'answers'         => [],
        ]);

        $this->actingAs($other)
            ->get(route('quiz.attempts.show', $attempt))
            ->assertForbidden();
    }

    public function test_admin_can_see_any_attempt_detail(): void
    {
        $admin  = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $viewer = $this->approvedViewer();
        ['quiz' => $quiz] = $this->quizWithQuestions(Quiz::STATUS_PUBLISHED, 2);

        $attempt = QuizAttempt::create([
            'user_id'         => $viewer->id,
            'quiz_id'         => $quiz->id,
            'score'           => 1,
            'total_questions' => 2,
            'answers'         => [],
        ]);

        $this->actingAs($admin)
            ->get(route('quiz.attempts.show', $attempt))
            ->assertOk()
            ->assertViewIs('quiz.attempt');
    }

    public function test_attempt_detail_kpi_calculation(): void
    {
        $viewer    = $this->approvedViewer();
        $quiz      = Quiz::factory()->create(['status' => Quiz::STATUS_PUBLISHED, 'max_questions' => 5, 'max_errors' => 3]);
        $questions = Question::factory()->count(5)->create(['is_true' => 1]);
        $quiz->questions()->attach($questions->pluck('id'));

        // First 3 correct (correct=1 matches is_true=1), last 2 wrong (correct=0 ≠ is_true=1)
        $answers = [];
        foreach ($questions->values() as $i => $q) {
            $answers[$q->id] = [
                'correct'            => $i < 3 ? 1 : 0,
                'answered_at'        => now()->timestamp,
                'time_spent_seconds' => 10,
                'position'           => $i + 1,
            ];
        }

        $attempt = QuizAttempt::create([
            'user_id'         => $viewer->id,
            'quiz_id'         => $quiz->id,
            'score'           => 3,
            'total_questions' => 5,
            'answers'         => $answers,
        ]);

        $response = $this->actingAs($viewer)->get(route('quiz.attempts.show', $attempt));

        $response->assertOk();
        $stats = $response->viewData('stats');

        $this->assertSame(5, $stats['total']);
        $this->assertSame(3, $stats['correct']);
        $this->assertSame(2, $stats['wrong']);
        $this->assertSame(0, $stats['not_answered']);
        $this->assertTrue($stats['passed']); // wrong(2) <= max_errors(3)
    }

    public function test_attempt_detail_shows_promosso_when_passed(): void
    {
        $viewer    = $this->approvedViewer();
        $quiz      = Quiz::factory()->create(['status' => Quiz::STATUS_PUBLISHED, 'max_questions' => 5, 'max_errors' => 3]);
        $questions = Question::factory()->count(5)->create(['is_true' => 1]);
        $quiz->questions()->attach($questions->pluck('id'));

        // 2 wrong answers → passed (2 ≤ 3)
        $answers = [];
        foreach ($questions->values() as $i => $q) {
            $answers[$q->id] = ['correct' => $i < 3 ? 1 : 0, 'answered_at' => null, 'time_spent_seconds' => null, 'position' => $i + 1];
        }

        $attempt = QuizAttempt::create(['user_id' => $viewer->id, 'quiz_id' => $quiz->id, 'score' => 3, 'total_questions' => 5, 'answers' => $answers]);

        $this->actingAs($viewer)->get(route('quiz.attempts.show', $attempt))->assertSee('PROMOSSO');
    }

    public function test_attempt_detail_shows_rimandato_when_failed(): void
    {
        $viewer    = $this->approvedViewer();
        $quiz      = Quiz::factory()->create(['status' => Quiz::STATUS_PUBLISHED, 'max_questions' => 5, 'max_errors' => 3]);
        $questions = Question::factory()->count(5)->create(['is_true' => 1]);
        $quiz->questions()->attach($questions->pluck('id'));

        // 4 wrong answers → failed (4 > 3)
        $answers = [];
        foreach ($questions->values() as $i => $q) {
            $answers[$q->id] = ['correct' => $i < 1 ? 1 : 0, 'answered_at' => null, 'time_spent_seconds' => null, 'position' => $i + 1];
        }

        $attempt = QuizAttempt::create(['user_id' => $viewer->id, 'quiz_id' => $quiz->id, 'score' => 1, 'total_questions' => 5, 'answers' => $answers]);

        $this->actingAs($viewer)->get(route('quiz.attempts.show', $attempt))->assertSee('RIMANDATO');
    }

    public function test_update_attempt_recalculates_score_from_answers(): void
    {
        $viewer = $this->approvedViewer();
        ['quiz' => $quiz, 'questions' => $questions] = $this->quizWithQuestions(Quiz::STATUS_PUBLISHED, 4);

        // Attempt vuoto creato a monte (simulando l'apertura della pagina /quiz/{id}/play).
        $attempt = QuizAttempt::create([
            'user_id'         => $viewer->id,
            'quiz_id'         => $quiz->id,
            'score'           => 0,
            'total_questions' => $questions->count(),
            'answers'         => [],
        ]);

        // 3 risposte corrette su 4: invertiamo la prima risposta rispetto al valore atteso.
        $answers = [];
        foreach ($questions as $index => $q) {
            $answers[$q->id] = [
                'correct'            => $index === 0 ? (int) !$q->is_true : (int) $q->is_true,
                'answered_at'        => null,
                'time_spent_seconds' => null,
                'position'           => $index + 1,
            ];
        }

        $response = $this->actingAs($viewer)->putJson(
            route('quiz.attempts.update', $attempt),
            ['answers' => $answers],
        );

        $response->assertOk()
            ->assertJson(['success' => true, 'score' => 3]);

        $this->assertDatabaseHas('quiz_attempts', [
            'id'              => $attempt->id,
            'score'           => 3,
            'total_questions' => 4,
        ]);
    }
}
