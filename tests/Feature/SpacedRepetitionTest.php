<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\LearnedQuestion;
use App\Models\Question;
use App\Models\QuestionReview;
use App\Models\User;
use App\Services\SpacedRepetitionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SpacedRepetitionTest extends TestCase
{
    use RefreshDatabase;

    private SpacedRepetitionService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(SpacedRepetitionService::class);
    }

    private function viewer(): User
    {
        return User::factory()->create(['role' => User::ROLE_VIEWER]);
    }

    /** Crea un QuestionReview in memoria (senza persistere) con i valori dati. */
    private function makeReview(array $attrs = []): QuestionReview
    {
        $review = new QuestionReview(array_merge([
            'interval_days' => 1,
            'ease_factor'   => 2.50,
            'repetitions'   => 0,
        ], $attrs));

        return $review;
    }

    /*
    |--------------------------------------------------------------------------
    | ALGORITMO SM-2 (test su calculateNextReview, nessun DB)
    |--------------------------------------------------------------------------
    */

    public function test_first_correct_answer_sets_interval_1_repetitions_1(): void
    {
        $review  = $this->makeReview(['repetitions' => 0, 'interval_days' => 1]);
        $result  = $this->service->calculateNextReview($review, true);

        $this->assertEquals(1, $result['interval_days']);
        $this->assertEquals(1, $result['repetitions']);
    }

    public function test_second_correct_answer_sets_interval_3_repetitions_2(): void
    {
        // Stato dopo la prima risposta corretta: repetitions=1, interval_days=1
        $review  = $this->makeReview(['repetitions' => 1, 'interval_days' => 1]);
        $result  = $this->service->calculateNextReview($review, true);

        $this->assertEquals(3, $result['interval_days']);
        $this->assertEquals(2, $result['repetitions']);
    }

    public function test_third_correct_answer_multiplies_by_ease_factor(): void
    {
        // Stato dopo la seconda corretta: repetitions=2, interval_days=3, ease_factor=2.70 (2.50+0.10+0.10)
        $easeFactor = 2.70;
        $review     = $this->makeReview(['repetitions' => 2, 'interval_days' => 3, 'ease_factor' => $easeFactor]);
        $result     = $this->service->calculateNextReview($review, true);

        $expected = (int) min(round(3 * $easeFactor), 365);
        $this->assertEquals($expected, $result['interval_days']);
        $this->assertEquals(3, $result['repetitions']);
    }

    public function test_wrong_answer_resets_repetitions_and_interval(): void
    {
        $review = $this->makeReview(['repetitions' => 5, 'interval_days' => 30, 'ease_factor' => 2.50]);
        $result = $this->service->calculateNextReview($review, false);

        $this->assertEquals(0, $result['repetitions']);
        $this->assertEquals(1, $result['interval_days']);
    }

    public function test_ease_factor_decreases_on_wrong_answer(): void
    {
        $review = $this->makeReview(['ease_factor' => 2.50, 'repetitions' => 3]);
        $result = $this->service->calculateNextReview($review, false);

        $this->assertEquals(round(2.50 - 0.20, 2), $result['ease_factor']);
    }

    public function test_ease_factor_floor_at_1_30(): void
    {
        // ease già al minimo: non deve scendere sotto 1.30
        $review = $this->makeReview(['ease_factor' => 1.30, 'repetitions' => 2]);
        $result = $this->service->calculateNextReview($review, false);

        $this->assertEquals(1.30, $result['ease_factor']);
    }

    public function test_ease_factor_ceiling_at_2_80(): void
    {
        // ease già al massimo: non deve salire oltre 2.80
        $review = $this->makeReview(['ease_factor' => 2.80, 'repetitions' => 3, 'interval_days' => 5]);
        $result = $this->service->calculateNextReview($review, true);

        $this->assertEquals(2.80, $result['ease_factor']);
    }

    public function test_interval_capped_at_365_days(): void
    {
        // interval grande + ease massimo: deve rimanere a 365
        $review = $this->makeReview(['ease_factor' => 2.80, 'repetitions' => 10, 'interval_days' => 300]);
        $result = $this->service->calculateNextReview($review, true);

        $this->assertLessThanOrEqual(365, $result['interval_days']);
    }

    /*
    |--------------------------------------------------------------------------
    | INTEGRAZIONE CON DB
    |--------------------------------------------------------------------------
    */

    public function test_learned_question_excluded_from_due(): void
    {
        $user     = $this->viewer();
        $question = Question::factory()->create(['is_true' => 1]);

        // Crea un review in scadenza
        QuestionReview::factory()->create([
            'user_id'       => $user->id,
            'question_id'   => $question->id,
            'next_review_at' => now()->subHour(),
        ]);

        // Segna la domanda come imparata
        LearnedQuestion::create([
            'user_id'     => $user->id,
            'question_id' => $question->id,
            'marked_at'   => now(),
        ]);

        $due = $this->service->getDueQuestions($user);

        $this->assertCount(0, $due);
    }

    public function test_get_upcoming_count_returns_correct_counts(): void
    {
        // Pin to mid-week so "tomorrow" always falls within the same ISO week.
        $this->travelTo(now()->startOfWeek()->addDays(2));

        $user = $this->viewer();

        // 2 domande scadute oggi
        QuestionReview::factory()->count(2)->create([
            'user_id'        => $user->id,
            'next_review_at' => now()->subHour(),
        ]);

        // 1 domanda scade domani
        QuestionReview::factory()->create([
            'user_id'        => $user->id,
            'next_review_at' => now()->addDay()->midDay(),
        ]);

        $counts = $this->service->getUpcomingCount($user);

        $this->assertEquals(2, $counts['due_today']);
        $this->assertEquals(1, $counts['due_tomorrow']);
        $this->assertGreaterThanOrEqual(3, $counts['due_this_week']);
    }

    public function test_study_mode_creates_question_review(): void
    {
        $user     = $this->viewer();
        $category = Category::factory()->create();
        $question = Question::factory()->create([
            'category_id' => $category->id,
            'is_true'     => 1,
        ]);

        // Avvia sessione studio con la categoria
        $this->actingAs($user)->post(route('study.start'), [
            'source'      => 'category',
            'category_id' => $category->id,
        ]);

        // Chiama flag con risposta corretta (is_true=1, answer=1)
        $this->actingAs($user)->post(route('study.flag', $question->id), [
            'answer' => 1,
        ]);

        $this->assertDatabaseHas('question_reviews', [
            'user_id'     => $user->id,
            'question_id' => $question->id,
        ]);
    }

    public function test_quiz_attempt_creates_question_review_for_each_answer(): void
    {
        $user      = $this->viewer();
        $questions = Question::factory()->count(3)->create(['is_true' => 1]);

        $quiz = \App\Models\Quiz::factory()->create(['status' => \App\Models\Quiz::STATUS_PUBLISHED]);
        $quiz->questions()->attach($questions->pluck('id'));

        $answers = [];
        foreach ($questions as $q) {
            $answers[$q->id] = 1;
        }

        $this->actingAs($user)->post(route('quiz.attempts.store'), [
            'quiz_id'  => $quiz->id,
            'answers'  => $answers,
            'duration' => 30,
        ]);

        foreach ($questions as $q) {
            $this->assertDatabaseHas('question_reviews', [
                'user_id'     => $user->id,
                'question_id' => $q->id,
            ]);
        }
    }
}
