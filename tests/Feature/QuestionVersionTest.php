<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Question;
use App\Models\QuestionVersion;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\User;
use App\Services\QuestionService;
use App\Services\QuestionVersionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class QuestionVersionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(\App\Http\Middleware\EnsureTwoFactorAuthenticated::class);
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    private function viewer(): User
    {
        return User::factory()->create(['role' => 'viewer']);
    }

    private function makeQuestion(array $attrs = []): Question
    {
        $category = Category::factory()->create();
        return Question::factory()->create(array_merge([
            'category_id' => $category->id,
            'question'    => 'Testo originale',
            'is_true'     => true,
            'image'       => null,
        ], $attrs));
    }

    // -------------------------------------------------------------------------
    // 1. Modifica il testo → crea nuova versione
    // -------------------------------------------------------------------------

    public function test_modifying_question_text_creates_new_version(): void
    {
        $admin    = $this->admin();
        $question = $this->makeQuestion();
        $question->createVersion(); // V1 (stato attuale)

        $service = app(QuestionService::class);
        $this->actingAs($admin);
        $service->update($question, [
            'question'    => 'Testo modificato',
            'is_true'     => $question->is_true,
            'category_id' => $question->category_id,
        ]);

        $this->assertDatabaseHas('question_versions', [
            'question_id'    => $question->id,
            'version_number' => 2,
            'question'       => 'Testo modificato',
        ]);
        $this->assertEquals(2, $question->fresh()->versions()->count());
    }

    // -------------------------------------------------------------------------
    // 2. Modifica campo NON versionabile (mit_code) → nessuna versione
    // -------------------------------------------------------------------------

    public function test_modifying_non_versionable_field_does_not_create_version(): void
    {
        $admin    = $this->admin();
        $question = $this->makeQuestion();
        $question->createVersion(); // V1

        $countBefore = QuestionVersion::where('question_id', $question->id)->count();

        $service = app(QuestionService::class);
        $this->actingAs($admin);
        $service->update($question, [
            'question'    => $question->question, // invariato
            'is_true'     => $question->is_true,
            'category_id' => $question->category_id,
            'mit_code'    => 'MIT-999',           // campo non versionabile
        ]);

        $this->assertEquals($countBefore, QuestionVersion::where('question_id', $question->id)->count());
    }

    // -------------------------------------------------------------------------
    // 3. QuizAttempt registra il question_version_id corretto
    // -------------------------------------------------------------------------

    public function test_quiz_attempt_records_question_version_id(): void
    {
        $viewer   = $this->viewer();
        $category = Category::factory()->create();
        $question = Question::factory()->create([
            'category_id' => $category->id,
            'is_true'     => true,
        ]);
        $version = $question->createVersion(); // V1

        $quiz = Quiz::factory()->create(['status' => 'published']);
        $quiz->questions()->attach($question->id);

        $this->actingAs($viewer);
        $response = $this->withSession(['2fa_verified' => true])
            ->postJson(route('quiz.attempts.store'), [
                'quiz_id'  => $quiz->id,
                'answers'  => [
                    $question->id => ['correct' => 1, 'answered_at' => null, 'time_spent_seconds' => null, 'position' => 1],
                ],
                'duration' => 10,
            ]);

        $response->assertOk();

        $attempt = QuizAttempt::latest()->first();
        $this->assertEquals($version->id, $attempt->getAnswerVersionId($question->id));
    }

    // -------------------------------------------------------------------------
    // 4. Pagina dettaglio tentativo mostra testo storico dopo modifica
    // -------------------------------------------------------------------------

    public function test_attempt_detail_shows_historical_version_after_question_edit(): void
    {
        $admin    = $this->admin();
        $viewer   = $this->viewer();
        $category = Category::factory()->create();
        $question = Question::factory()->create([
            'category_id' => $category->id,
            'question'    => 'Testo originale',
            'is_true'     => true,
        ]);
        $v1 = $question->createVersion(); // V1

        $quiz = Quiz::factory()->create(['status' => 'published']);
        $quiz->questions()->attach($question->id);

        // Viewer esegue tentativo → registra V1
        $attempt = QuizAttempt::create([
            'user_id'         => $viewer->id,
            'quiz_id'         => $quiz->id,
            'score'           => 1,
            'total_questions' => 1,
            'answers'         => [
                $question->id => [
                    'correct'              => 1,
                    'answered_at'          => null,
                    'time_spent_seconds'   => null,
                    'position'             => 1,
                    'question_version_id'  => $v1->id,
                ],
            ],
        ]);

        // Admin modifica il testo → V2 viene creata
        $this->actingAs($admin);
        app(QuestionService::class)->update($question, [
            'question'    => 'Testo modificato',
            'is_true'     => $question->is_true,
            'category_id' => $question->category_id,
        ]);

        // La pagina di dettaglio deve mostrare il testo storico (V1)
        $response = $this->actingAs($viewer)->withSession(['2fa_verified' => true])
            ->get(route('quiz.attempts.show', $attempt));

        $response->assertOk();
        $response->assertSee('Testo originale');
        $response->assertSee('Versione storica');
        $response->assertDontSee('Testo modificato');
    }

    // -------------------------------------------------------------------------
    // 5. Revisione errori mostra testo storico dopo modifica
    // -------------------------------------------------------------------------

    public function test_review_errors_shows_historical_version_after_edit(): void
    {
        $admin    = $this->admin();
        $viewer   = $this->viewer();
        $category = Category::factory()->create();
        $question = Question::factory()->create([
            'category_id' => $category->id,
            'question'    => 'Testo originale errore',
            'is_true'     => true,
        ]);
        $v1 = $question->createVersion();

        $quiz = Quiz::factory()->create(['status' => 'published']);
        $quiz->questions()->attach($question->id);

        // Tentativo con risposta sbagliata che referenzia V1
        QuizAttempt::create([
            'user_id'         => $viewer->id,
            'quiz_id'         => $quiz->id,
            'score'           => 0,
            'total_questions' => 1,
            'answers'         => [
                $question->id => [
                    'correct'             => 0, // sbagliata
                    'answered_at'         => now()->timestamp,
                    'time_spent_seconds'  => null,
                    'position'            => 1,
                    'question_version_id' => $v1->id,
                ],
            ],
        ]);

        // Admin modifica il testo
        $this->actingAs($admin);
        app(QuestionService::class)->update($question, [
            'question'    => 'Testo modificato errore',
            'is_true'     => $question->is_true,
            'category_id' => $question->category_id,
        ]);

        $response = $this->actingAs($viewer)->withSession(['2fa_verified' => true])
            ->get(route('viewer.review-errors.index'));

        $response->assertOk();
        $response->assertSee('Testo originale errore');
        $response->assertSee('Versione storica');
    }

    // -------------------------------------------------------------------------
    // 6. Tentativo pre-versionamento (senza version_id) → fallback senza errori
    // -------------------------------------------------------------------------

    public function test_legacy_attempt_without_version_id_falls_back_gracefully(): void
    {
        $viewer   = $this->viewer();
        $category = Category::factory()->create();
        $question = Question::factory()->create([
            'category_id' => $category->id,
            'question'    => 'Testo corrente',
            'is_true'     => true,
        ]);

        $quiz = Quiz::factory()->create(['status' => 'published']);
        $quiz->questions()->attach($question->id);

        // Tentativo legacy: nessun question_version_id
        $attempt = QuizAttempt::create([
            'user_id'         => $viewer->id,
            'quiz_id'         => $quiz->id,
            'score'           => 1,
            'total_questions' => 1,
            'answers'         => [
                $question->id => [
                    'correct'            => 1,
                    'answered_at'        => null,
                    'time_spent_seconds' => null,
                    'position'           => 1,
                    // nessun question_version_id
                ],
            ],
        ]);

        $response = $this->actingAs($viewer)->withSession(['2fa_verified' => true])
            ->get(route('quiz.attempts.show', $attempt));

        $response->assertOk();
        $response->assertSee('Testo corrente');
        // Nessun badge "Versione storica" perché non c'è una versione referenziata
        $response->assertDontSee('Versione storica');
    }

    // -------------------------------------------------------------------------
    // 7. Ripristino versione storica → crea nuova versione in cima senza cancellare storia
    // -------------------------------------------------------------------------

    public function test_restoring_historical_version_creates_new_version_and_preserves_history(): void
    {
        $admin    = $this->admin();
        $question = $this->makeQuestion(['question' => 'Testo A']);
        $v1       = $question->createVersion(); // V1 = Testo A

        $this->actingAs($admin);
        app(QuestionService::class)->update($question, [
            'question'    => 'Testo B',
            'is_true'     => $question->is_true,
            'category_id' => $question->category_id,
        ]); // V2 = Testo B

        $question->refresh();
        $this->assertEquals('Testo B', $question->question);
        $this->assertEquals(2, $question->versions()->count());

        // Ripristina V1
        app(QuestionVersionService::class)->restoreVersion($question, $v1);

        $question->refresh();
        $this->assertEquals('Testo A', $question->question);
        // Deve esistere V3 = Testo A (snapshot del dopo il ripristino)
        $this->assertEquals(3, $question->versions()->count());
        $this->assertEquals('Testo A', $question->versions()->first()->question);
        // V1 e V2 intatte
        $this->assertDatabaseHas('question_versions', ['question_id' => $question->id, 'version_number' => 1]);
        $this->assertDatabaseHas('question_versions', ['question_id' => $question->id, 'version_number' => 2]);
    }

    // -------------------------------------------------------------------------
    // 8. Data-migration crea V1 per ogni domanda esistente (idempotente)
    // -------------------------------------------------------------------------

    public function test_data_migration_creates_version_1_for_existing_questions(): void
    {
        // Simula una domanda esistente senza versioni
        $question = $this->makeQuestion();
        $this->assertDatabaseMissing('question_versions', ['question_id' => $question->id]);

        // Esegui la logica della data-migration
        $alreadyVersioned = QuestionVersion::where('version_number', 1)
            ->pluck('question_id')
            ->flip();

        Question::query()->lazy()->each(function (Question $q) use ($alreadyVersioned) {
            if ($alreadyVersioned->has($q->id)) {
                return;
            }
            QuestionVersion::create([
                'question_id'    => $q->id,
                'version_number' => 1,
                'question'       => $q->question,
                'is_true'        => $q->is_true,
                'image'          => $q->image,
                'category_id'    => $q->category_id,
                'created_by'     => null,
                'created_at'     => $q->created_at ?? now(),
            ]);
        });

        $this->assertDatabaseHas('question_versions', [
            'question_id'    => $question->id,
            'version_number' => 1,
            'question'       => $question->question,
        ]);

        // Seconda esecuzione: idempotente, nessun duplicato
        $alreadyVersioned2 = QuestionVersion::where('version_number', 1)
            ->pluck('question_id')
            ->flip();

        Question::query()->lazy()->each(function (Question $q) use ($alreadyVersioned2) {
            if ($alreadyVersioned2->has($q->id)) {
                return;
            }
            QuestionVersion::create([
                'question_id'    => $q->id,
                'version_number' => 1,
                'question'       => $q->question,
                'is_true'        => $q->is_true,
                'image'          => $q->image,
                'category_id'    => $q->category_id,
                'created_by'     => null,
                'created_at'     => $q->created_at ?? now(),
            ]);
        });

        $count = QuestionVersion::where('question_id', $question->id)->where('version_number', 1)->count();
        $this->assertEquals(1, $count, 'La data-migration deve essere idempotente');
    }

    // -------------------------------------------------------------------------
    // 9. Livewire QuestionVersionHistory: ripristino tramite componente
    // -------------------------------------------------------------------------

    public function test_livewire_restore_version_works(): void
    {
        $admin    = $this->admin();
        $question = $this->makeQuestion(['question' => 'Testo iniziale']);
        $v1       = $question->createVersion();

        $this->actingAs($admin);
        app(QuestionService::class)->update($question, [
            'question'    => 'Testo aggiornato',
            'is_true'     => $question->is_true,
            'category_id' => $question->category_id,
        ]);

        Livewire::actingAs($admin)
            ->test(\App\Http\Livewire\QuestionVersionHistory::class, ['questionId' => $question->id])
            ->call('restoreVersion', $v1->id);

        $this->assertEquals('Testo iniziale', $question->fresh()->question);
    }

    // -------------------------------------------------------------------------
    // 10. getAnswerVersionId restituisce null per risposte senza campo
    // -------------------------------------------------------------------------

    public function test_get_answer_version_id_returns_null_for_legacy_flat_format(): void
    {
        $attempt = new QuizAttempt();
        $attempt->answers = ['5' => 1]; // formato flat legacy

        $this->assertNull($attempt->getAnswerVersionId(5));
    }

    public function test_get_answer_version_id_returns_correct_value_for_extended_format(): void
    {
        $attempt = new QuizAttempt();
        $attempt->answers = [
            '5' => [
                'correct'             => 1,
                'answered_at'         => null,
                'time_spent_seconds'  => null,
                'position'            => 1,
                'question_version_id' => 42,
            ],
        ];

        $this->assertEquals(42, $attempt->getAnswerVersionId(5));
    }
}
