<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\LearnedQuestion;
use App\Models\Question;
use App\Models\QuizAttempt;
use App\Models\User;
use App\Services\ReviewErrorsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReviewErrorsTest extends TestCase
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

    /**
     * Crea un QuizAttempt con risposte nel formato esteso usato dal servizio.
     * $answersByQuestionId: [question_id => bool (true=corretta, false=sbagliata)]
     */
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

    // ──────────────────────────────────────────────────────────────────────────
    // ACCESS
    // ──────────────────────────────────────────────────────────────────────────

    public function test_index_requires_authentication(): void
    {
        $this->get(route('viewer.review-errors.index'))
            ->assertRedirect(route('login'));
    }

    public function test_admin_cannot_access_review_errors(): void
    {
        $this->actingAs($this->admin())
            ->get(route('viewer.review-errors.index'))
            ->assertForbidden();
    }

    public function test_viewer_can_access_review_errors(): void
    {
        $this->actingAs($this->viewer())
            ->get(route('viewer.review-errors.index'))
            ->assertOk();
    }

    // ──────────────────────────────────────────────────────────────────────────
    // ISOLAMENTO DATI
    // ──────────────────────────────────────────────────────────────────────────

    public function test_viewer_sees_only_own_errors(): void
    {
        $viewer1 = $this->viewer();
        $viewer2 = $this->viewer();
        $q1      = Question::factory()->create(['question' => 'Domanda del viewer 1']);
        $q2      = Question::factory()->create(['question' => 'Domanda del viewer 2']);

        $this->makeAttempt($viewer1, [$q1->id => false]);
        $this->makeAttempt($viewer2, [$q2->id => false]);

        $response = $this->actingAs($viewer1)->get(route('viewer.review-errors.index'));

        $response->assertOk();
        $response->assertSee($q1->question);
        $response->assertDontSee($q2->question);
    }

    // ──────────────────────────────────────────────────────────────────────────
    // LOGICA ERRORI
    // ──────────────────────────────────────────────────────────────────────────

    public function test_correctly_answered_question_does_not_appear(): void
    {
        $viewer   = $this->viewer();
        $question = Question::factory()->create(['question' => 'Domanda corretta']);

        $this->makeAttempt($viewer, [$question->id => true]);

        $response = $this->actingAs($viewer)->get(route('viewer.review-errors.index'));

        $response->assertOk();
        $response->assertDontSee($question->question);
    }

    public function test_wrongly_answered_question_appears(): void
    {
        $viewer   = $this->viewer();
        $question = Question::factory()->create(['question' => 'Domanda sbagliata']);

        $this->makeAttempt($viewer, [$question->id => false]);

        $response = $this->actingAs($viewer)->get(route('viewer.review-errors.index'));

        $response->assertOk();
        $response->assertSee($question->question);
    }

    public function test_incomplete_attempt_not_counted(): void
    {
        $viewer   = $this->viewer();
        $question = Question::factory()->create(['question' => 'Domanda da tentativo abbandonato']);

        // Tentativo abbandonato: answers array vuoto (es. simulatore avviato e mai risposto)
        QuizAttempt::factory()->create([
            'user_id' => $viewer->id,
            'answers' => [],
        ]);

        $response = $this->actingAs($viewer)->get(route('viewer.review-errors.index'));

        $response->assertOk();
        $response->assertDontSee($question->question);
    }

    public function test_null_answers_attempt_not_counted(): void
    {
        $viewer   = $this->viewer();
        $question = Question::factory()->create(['question' => 'Domanda da tentativo null']);

        QuizAttempt::factory()->create([
            'user_id' => $viewer->id,
            'answers' => null,
        ]);

        $response = $this->actingAs($viewer)->get(route('viewer.review-errors.index'));

        $response->assertOk();
        $response->assertDontSee($question->question);
    }

    // ──────────────────────────────────────────────────────────────────────────
    // FILTRI
    // ──────────────────────────────────────────────────────────────────────────

    public function test_category_filter_shows_only_matching_category(): void
    {
        $viewer = $this->viewer();
        $cat1   = Category::factory()->create(['name' => 'Categoria A']);
        $cat2   = Category::factory()->create(['name' => 'Categoria B']);
        $q1     = Question::factory()->create(['category_id' => $cat1->id, 'question' => 'Domanda cat A']);
        $q2     = Question::factory()->create(['category_id' => $cat2->id, 'question' => 'Domanda cat B']);

        $this->makeAttempt($viewer, [$q1->id => false, $q2->id => false]);

        $response = $this->actingAs($viewer)->get(
            route('viewer.review-errors.index', ['category_id' => $cat1->id])
        );

        $response->assertOk();
        $response->assertSee($q1->question);
        $response->assertDontSee($q2->question);
    }

    public function test_last_attempts_limit_is_respected(): void
    {
        $viewer   = $this->viewer();
        $question = Question::factory()->create(['question' => 'Domanda fuori limite']);

        // Creo 5 tentativi recenti con created_at esplicito per garantire l'ordinamento
        for ($i = 0; $i < 5; $i++) {
            $other = Question::factory()->create();
            $this->makeAttempt($viewer, [$other->id => false], ['created_at' => now()]);
        }

        // La domanda da verificare era sbagliata in un tentativo più vecchio
        $oldAttempt = $this->makeAttempt($viewer, [$question->id => false], [
            'created_at' => now()->subDays(10),
            'updated_at' => now()->subDays(10),
        ]);

        // Chiedo solo gli ultimi 5 tentativi: il tentativo vecchio non deve essere incluso
        $response = $this->actingAs($viewer)->get(
            route('viewer.review-errors.index', ['last_attempts' => 5])
        );

        $response->assertOk();
        $response->assertDontSee($question->question);
    }

    // ──────────────────────────────────────────────────────────────────────────
    // TOGGLE IMPARATA
    // ──────────────────────────────────────────────────────────────────────────

    public function test_mark_as_learned_excludes_question_from_errors(): void
    {
        $viewer   = $this->viewer();
        $question = Question::factory()->create(['question' => 'Domanda imparata']);

        $this->makeAttempt($viewer, [$question->id => false]);

        // Prima di marcare: la domanda appare
        $this->actingAs($viewer)
            ->get(route('viewer.review-errors.index'))
            ->assertSee($question->question);

        // Marca come imparata
        $this->actingAs($viewer)
            ->post(route('viewer.review-errors.learned.store', $question))
            ->assertRedirect();

        $this->assertDatabaseHas('learned_questions', [
            'user_id'     => $viewer->id,
            'question_id' => $question->id,
        ]);

        // Dopo aver marcato: la domanda non appare più nella lista errori
        $this->actingAs($viewer)
            ->get(route('viewer.review-errors.index'))
            ->assertDontSee($question->question);
    }

    public function test_unmark_as_learned_reinserts_in_errors(): void
    {
        $viewer   = $this->viewer();
        $question = Question::factory()->create(['question' => 'Domanda da reinserire']);

        $this->makeAttempt($viewer, [$question->id => false]);

        LearnedQuestion::create([
            'user_id'     => $viewer->id,
            'question_id' => $question->id,
            'marked_at'   => now(),
        ]);

        // La domanda non appare nella lista errori (è imparata)
        $this->actingAs($viewer)
            ->get(route('viewer.review-errors.index'))
            ->assertDontSee($question->question);

        // Deseleziona "imparata"
        $this->actingAs($viewer)
            ->delete(route('viewer.review-errors.learned.destroy', $question))
            ->assertRedirect();

        $this->assertDatabaseMissing('learned_questions', [
            'user_id'     => $viewer->id,
            'question_id' => $question->id,
        ]);

        // La domanda riappare nella lista errori
        $this->actingAs($viewer)
            ->get(route('viewer.review-errors.index'))
            ->assertSee($question->question);
    }

    public function test_mark_as_learned_is_idempotent(): void
    {
        $viewer   = $this->viewer();
        $question = Question::factory()->create();
        $service  = app(ReviewErrorsService::class);

        $service->markAsLearned($viewer, $question->id);
        $service->markAsLearned($viewer, $question->id); // chiamata doppia: nessun errore

        $this->assertDatabaseCount('learned_questions', 1);
    }

    public function test_learned_is_personal_other_user_unaffected(): void
    {
        $viewer1  = $this->viewer();
        $viewer2  = $this->viewer();
        $question = Question::factory()->create(['question' => 'Domanda condivisa']);

        $this->makeAttempt($viewer1, [$question->id => false]);
        $this->makeAttempt($viewer2, [$question->id => false]);

        // Viewer1 marca come imparata
        $this->actingAs($viewer1)
            ->post(route('viewer.review-errors.learned.store', $question))
            ->assertRedirect();

        // Viewer2 non è influenzato: la domanda appare ancora nei suoi errori
        $this->actingAs($viewer2)
            ->get(route('viewer.review-errors.index'))
            ->assertSee($question->question);
    }

    // ──────────────────────────────────────────────────────────────────────────
    // CASCATA
    // ──────────────────────────────────────────────────────────────────────────

    public function test_cascade_delete_removes_learned_on_user_deletion(): void
    {
        $viewer   = $this->viewer();
        $question = Question::factory()->create();

        LearnedQuestion::create([
            'user_id'     => $viewer->id,
            'question_id' => $question->id,
            'marked_at'   => now(),
        ]);

        $this->assertDatabaseHas('learned_questions', ['user_id' => $viewer->id]);

        $viewer->delete();

        $this->assertDatabaseMissing('learned_questions', ['user_id' => $viewer->id]);
    }

    public function test_show_learned_toggle_shows_learned_questions(): void
    {
        $viewer   = $this->viewer();
        $question = Question::factory()->create(['question' => 'Domanda da mostrare come imparata']);

        $this->makeAttempt($viewer, [$question->id => false]);

        LearnedQuestion::create([
            'user_id'     => $viewer->id,
            'question_id' => $question->id,
            'marked_at'   => now(),
        ]);

        $response = $this->actingAs($viewer)->get(
            route('viewer.review-errors.index', ['show_learned' => 1])
        );

        $response->assertOk();
        $response->assertSee($question->question);
    }
}
