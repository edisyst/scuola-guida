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

        $answers = $questions->mapWithKeys(fn (Question $q) => [$q->id => (int) $q->is_true])->all();

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

        $answers = $questions->mapWithKeys(fn (Question $q) => [$q->id => (int) $q->is_true])->all();

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

        // 3 risposte corrette su 4: invertiamo la prima rispetto al valore atteso.
        $answers = [];
        foreach ($questions as $index => $q) {
            $answers[$q->id] = $index === 0 ? (int) !$q->is_true : (int) $q->is_true;
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
