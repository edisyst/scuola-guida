<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\LicenseType;
use App\Models\Question;
use App\Models\QuizAttempt;
use App\Models\User;
use App\Services\SimulatorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class SimulatorTest extends TestCase
{
    use RefreshDatabase;

    private LicenseType $licenseType;

    protected function setUp(): void
    {
        parent::setUp();
        $this->licenseType = LicenseType::factory()->create();
    }

    /*
    |--------------------------------------------------------------------------
    | HELPERS
    |--------------------------------------------------------------------------
    */

    private function viewer(): User
    {
        return User::factory()->create([
            'role'                   => User::ROLE_VIEWER,
            'active_license_type_id' => $this->licenseType->id,
        ]);
    }

    /**
     * Crea una categoria con N domande utili per soddisfare la distribuzione.
     */
    private function seedCategoryWithQuestions(string $name, int $questions): Category
    {
        $category = Category::factory()->create(['name' => $name]);
        Question::factory()->count($questions)->create(['category_id' => $category->id]);

        return $category;
    }

    /**
     * Popola il DB con almeno N domande generiche (categoria random) per coprire
     * il target del simulatore in test che non richiedono la distribuzione precisa.
     */
    private function seedGenericPool(int $count = 60): void
    {
        $category = Category::factory()->create();
        Question::factory()->count($count)->create(['category_id' => $category->id]);
    }

    /*
    |--------------------------------------------------------------------------
    | TESTS
    |--------------------------------------------------------------------------
    */

    public function test_guest_cannot_access_simulator_index(): void
    {
        $this->get(route('simulator.index'))->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_view_simulator_index(): void
    {
        $response = $this->actingAs($this->viewer())->get(route('simulator.index'));

        $response->assertOk()
            ->assertViewIs('simulator.index')
            ->assertViewHasAll(['questions', 'timeLimit', 'maxErrors']);
    }

    public function test_start_creates_attempt_with_null_quiz_id_and_target_questions(): void
    {
        $this->seedGenericPool(40);

        $viewer = $this->viewer();
        $target = (int) config('simulator.questions');

        $response = $this->actingAs($viewer)->post(route('simulator.start'));

        $response->assertRedirect(route('simulator.play'));

        $attempt = QuizAttempt::where('user_id', $viewer->id)->first();

        $this->assertNotNull($attempt, 'Il tentativo deve essere creato.');
        $this->assertNull($attempt->quiz_id);
        $this->assertSame($target, $attempt->total_questions);
        $this->assertSame($attempt->id, session('simulator_attempt_id'));
        $this->assertCount($target, session('simulator_questions'));
    }

    public function test_start_with_empty_pool_redirects_with_error(): void
    {
        // Nessuna categoria né domanda nel DB.
        $this->actingAs($this->viewer())
            ->post(route('simulator.start'))
            ->assertRedirect(route('simulator.index'))
            ->assertSessionHas('error');

        $this->assertDatabaseCount('quiz_attempts', 0);
    }

    public function test_play_without_active_session_redirects_to_index(): void
    {
        $this->actingAs($this->viewer())
            ->get(route('simulator.play'))
            ->assertRedirect(route('simulator.index'))
            ->assertSessionHas('warning');
    }

    public function test_play_with_active_session_renders_view_with_questions(): void
    {
        $this->seedGenericPool(40);

        $viewer = $this->viewer();
        $this->actingAs($viewer)->post(route('simulator.start'));

        $response = $this->actingAs($viewer)->get(route('simulator.play'));

        $response->assertOk()
            ->assertViewIs('simulator.play')
            ->assertViewHasAll(['attempt', 'questionsJson', 'timeLimit', 'maxErrors']);
    }

    public function test_autosave_updates_attempt_answers_and_score(): void
    {
        $this->seedGenericPool(40);
        $viewer = $this->viewer();

        $this->actingAs($viewer)->post(route('simulator.start'));
        $attempt    = QuizAttempt::where('user_id', $viewer->id)->firstOrFail();
        $questionIds = session('simulator_questions');

        // Risposte: la prima corretta, le altre tutte sbagliate (per avere score = 1).
        $questions = Question::whereIn('id', $questionIds)->get()->keyBy('id');
        $answers   = [];
        foreach ($questionIds as $i => $qid) {
            $correct = (int) $questions[$qid]->is_true;
            $answers[$qid] = [
                'correct'     => $i === 0 ? $correct : 1 - $correct,
                'position'    => $i + 1,
                'answered_at' => time(),
            ];
        }

        $this->actingAs($viewer)
            ->putJson(route('simulator.autosave', $attempt), [
                'answers'  => $answers,
                'duration' => 120,
            ])
            ->assertOk()
            ->assertJson(['success' => true]);

        $attempt->refresh();
        $this->assertSame(1, $attempt->score);
        $this->assertSame(120, $attempt->duration);
    }

    public function test_autosave_blocks_attempts_belonging_to_other_users(): void
    {
        $this->seedGenericPool(40);
        $owner  = $this->viewer();
        $intruder = $this->viewer();

        $this->actingAs($owner)->post(route('simulator.start'));
        $attempt = QuizAttempt::where('user_id', $owner->id)->firstOrFail();

        $this->actingAs($intruder)
            ->putJson(route('simulator.autosave', $attempt), ['answers' => []])
            ->assertForbidden();
    }

    public function test_submit_finalizes_attempt_and_redirects_to_result(): void
    {
        $this->seedGenericPool(40);
        $viewer = $this->viewer();

        $this->actingAs($viewer)->post(route('simulator.start'));
        $attempt = QuizAttempt::where('user_id', $viewer->id)->firstOrFail();

        $response = $this->actingAs($viewer)->post(route('simulator.submit'), [
            'answers'  => [],
            'duration' => 60,
        ]);

        $response->assertRedirect(route('simulator.result', $attempt));
        $this->assertNull(session('simulator_attempt_id'));
        $this->assertNull(session('simulator_questions'));
    }

    public function test_destroy_clears_session_and_redirects(): void
    {
        $this->seedGenericPool(40);
        $viewer = $this->viewer();

        $this->actingAs($viewer)->post(route('simulator.start'));

        $this->actingAs($viewer)
            ->delete(route('simulator.destroy'))
            ->assertRedirect(route('simulator.index'));

        $this->assertNull(session('simulator_attempt_id'));
        $this->assertNull(session('simulator_questions'));
    }

    public function test_result_renders_for_owner_and_blocks_others(): void
    {
        $this->seedGenericPool(40);
        $owner    = $this->viewer();
        $intruder = $this->viewer();

        $this->actingAs($owner)->post(route('simulator.start'));
        $attempt = QuizAttempt::where('user_id', $owner->id)->firstOrFail();

        $this->actingAs($owner)
            ->post(route('simulator.submit'), ['answers' => [], 'duration' => 0]);

        $this->actingAs($owner)
            ->get(route('simulator.result', $attempt))
            ->assertOk()
            ->assertViewIs('simulator.result')
            ->assertViewHasAll(['attempt', 'rows', 'stats']);

        $this->actingAs($intruder)
            ->get(route('simulator.result', $attempt))
            ->assertForbidden();
    }

    public function test_build_question_list_logs_warning_when_category_missing(): void
    {
        // Pool generico per coprire l'integrazione di "extra" e non avere risultato vuoto.
        $this->seedGenericPool(40);

        // Distribuzione con solo nomi che NON esistono nel DB → triggera il log.
        config(['simulator.distribution' => ['__categoria_inesistente__' => 2]]);

        Log::spy();

        $this->actingAs($this->viewer());
        app(SimulatorService::class)->buildQuestionList();

        Log::shouldHaveReceived('warning')
            ->withArgs(fn ($msg) => is_string($msg) && str_contains($msg, 'categoria non trovata'))
            ->atLeast()->once();
    }

    public function test_quiz_attempt_with_default_does_not_throw_when_quiz_id_is_null(): void
    {
        $attempt = QuizAttempt::factory()->create([
            'quiz_id'         => null,
            'total_questions' => 30,
            'score'           => 0,
            'answers'         => [],
        ]);

        // Non deve lanciare eccezioni: withDefault() restituisce un Quiz vuoto con title impostato.
        $this->assertNotNull($attempt->quiz);
        $this->assertSame('Simulatore Esame', $attempt->quiz->title);
    }
}
