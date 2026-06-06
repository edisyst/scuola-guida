<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\LicenseType;
use App\Models\Question;
use App\Models\Quiz;
use App\Models\User;
use App\Services\StudyService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudyTest extends TestCase
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
     * @return array{quiz: Quiz, questions: \Illuminate\Database\Eloquent\Collection<int, Question>}
     */
    private function publishedQuizWithQuestions(int $count = 4): array
    {
        $quiz = Quiz::factory()->create([
            'status'        => Quiz::STATUS_PUBLISHED,
            'max_questions' => $count,
        ]);

        $questions = Question::factory()->count($count)->create();
        $quiz->questions()->attach($questions->pluck('id'));

        return ['quiz' => $quiz, 'questions' => $questions];
    }

    /*
    |--------------------------------------------------------------------------
    | TESTS
    |--------------------------------------------------------------------------
    */

    public function test_guest_cannot_access_study_index(): void
    {
        $this->get(route('study.index'))->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_view_study_index(): void
    {
        $response = $this->actingAs($this->viewer())->get(route('study.index'));

        $response->assertOk()
            ->assertViewIs('study.index')
            ->assertViewHasAll(['quizzes', 'categories', 'hasSession']);
    }

    public function test_start_with_quiz_source_initializes_session_and_redirects_to_play(): void
    {
        ['quiz' => $quiz, 'questions' => $questions] = $this->publishedQuizWithQuestions(3);

        $response = $this->actingAs($this->viewer())->post(route('study.start'), [
            'source'  => StudyService::SOURCE_QUIZ,
            'quiz_id' => $quiz->id,
        ]);

        $response->assertRedirect(route('study.play'));

        $this->assertSame(
            $questions->pluck('id')->all(),
            session(StudyService::KEY_QUESTIONS),
            'Le domande in sessione devono coincidere con quelle del quiz.'
        );
        $this->assertSame(0, session(StudyService::KEY_INDEX));
        $this->assertSame([], session(StudyService::KEY_FLAGGED));
    }

    public function test_start_with_category_source_initializes_session(): void
    {
        $category = Category::factory()->create();
        $category->licenseTypes()->attach($this->licenseType);
        Question::factory()->count(5)->create(['category_id' => $category->id]);

        $this->actingAs($this->viewer())->post(route('study.start'), [
            'source'      => StudyService::SOURCE_CATEGORY,
            'category_id' => $category->id,
        ])->assertRedirect(route('study.play'));

        $this->assertCount(5, session(StudyService::KEY_QUESTIONS));
    }

    public function test_start_with_random_source_uses_global_pool(): void
    {
        $cat = Category::factory()->create();
        $cat->licenseTypes()->attach($this->licenseType);
        Question::factory()->count(10)->create(['category_id' => $cat->id]);

        $this->actingAs($this->viewer())->post(route('study.start'), [
            'source' => StudyService::SOURCE_RANDOM,
        ])->assertRedirect(route('study.play'));

        $this->assertNotEmpty(session(StudyService::KEY_QUESTIONS));
        $this->assertLessThanOrEqual(StudyService::RANDOM_LIMIT, count(session(StudyService::KEY_QUESTIONS)));
    }

    public function test_start_validation_fails_when_source_is_missing(): void
    {
        $this->actingAs($this->viewer())
            ->post(route('study.start'), [])
            ->assertSessionHasErrors('source');
    }

    public function test_start_validation_fails_when_quiz_source_has_no_quiz_id(): void
    {
        $this->actingAs($this->viewer())
            ->post(route('study.start'), ['source' => StudyService::SOURCE_QUIZ])
            ->assertSessionHasErrors('quiz_id');
    }

    public function test_start_rejects_draft_quiz(): void
    {
        $quiz = Quiz::factory()->create(['status' => Quiz::STATUS_DRAFT]);
        Question::factory()->count(3)->create()->each(
            fn ($q) => $quiz->questions()->attach($q->id)
        );

        $this->actingAs($this->viewer())
            ->post(route('study.start'), [
                'source'  => StudyService::SOURCE_QUIZ,
                'quiz_id' => $quiz->id,
            ])
            ->assertSessionHasErrors('quiz_id');
    }

    public function test_play_redirects_to_index_when_no_session(): void
    {
        $this->actingAs($this->viewer())
            ->get(route('study.play'))
            ->assertRedirect(route('study.index'));
    }

    public function test_navigation_via_index_query_updates_session_index(): void
    {
        ['quiz' => $quiz] = $this->publishedQuizWithQuestions(5);
        $viewer           = $this->viewer();

        $this->actingAs($viewer)->post(route('study.start'), [
            'source'  => StudyService::SOURCE_QUIZ,
            'quiz_id' => $quiz->id,
        ]);

        $this->actingAs($viewer)
            ->get(route('study.play', ['index' => 3]))
            ->assertOk();

        $this->assertSame(3, session(StudyService::KEY_INDEX));
    }

    public function test_navigation_index_is_clamped_to_valid_range(): void
    {
        ['quiz' => $quiz] = $this->publishedQuizWithQuestions(3);
        $viewer           = $this->viewer();

        $this->actingAs($viewer)->post(route('study.start'), [
            'source'  => StudyService::SOURCE_QUIZ,
            'quiz_id' => $quiz->id,
        ]);

        // Index troppo grande → clampato all'ultimo (count-1 = 2)
        $this->actingAs($viewer)->get(route('study.play', ['index' => 99]))->assertOk();
        $this->assertSame(2, session(StudyService::KEY_INDEX));

        // Index negativo → clampato a 0
        $this->actingAs($viewer)->get(route('study.play', ['index' => -5]))->assertOk();
        $this->assertSame(0, session(StudyService::KEY_INDEX));
    }

    public function test_flag_toggle_adds_and_removes_question_from_session(): void
    {
        ['quiz' => $quiz, 'questions' => $questions] = $this->publishedQuizWithQuestions(3);
        $viewer  = $this->viewer();
        $first   = $questions->first();

        $this->actingAs($viewer)->post(route('study.start'), [
            'source'  => StudyService::SOURCE_QUIZ,
            'quiz_id' => $quiz->id,
        ]);

        // Toggle ON
        $this->actingAs($viewer)
            ->postJson(route('study.flag', ['question' => $first->id]), ['toggle' => true])
            ->assertOk()
            ->assertJson(['flagged' => true, 'flagged_count' => 1]);

        $this->assertContains($first->id, session(StudyService::KEY_FLAGGED));

        // Toggle OFF
        $this->actingAs($viewer)
            ->postJson(route('study.flag', ['question' => $first->id]), ['toggle' => true])
            ->assertOk()
            ->assertJson(['flagged' => false, 'flagged_count' => 0]);

        $this->assertNotContains($first->id, session(StudyService::KEY_FLAGGED, []));
    }

    public function test_flag_endpoint_records_answer_for_summary(): void
    {
        ['quiz' => $quiz, 'questions' => $questions] = $this->publishedQuizWithQuestions(3);
        $viewer = $this->viewer();
        $first  = $questions->first();

        $this->actingAs($viewer)->post(route('study.start'), [
            'source'  => StudyService::SOURCE_QUIZ,
            'quiz_id' => $quiz->id,
        ]);

        $this->actingAs($viewer)
            ->postJson(route('study.flag', ['question' => $first->id]), ['answer' => '1'])
            ->assertOk();

        $this->assertSame([$first->id => 1], session(StudyService::KEY_ANSWERS));
    }

    public function test_summary_shows_totals_and_flagged_questions(): void
    {
        ['quiz' => $quiz, 'questions' => $questions] = $this->publishedQuizWithQuestions(4);
        $viewer  = $this->viewer();
        $flagged = $questions->take(2);

        $this->actingAs($viewer)->post(route('study.start'), [
            'source'  => StudyService::SOURCE_QUIZ,
            'quiz_id' => $quiz->id,
        ]);

        foreach ($flagged as $q) {
            $this->actingAs($viewer)
                ->postJson(route('study.flag', ['question' => $q->id]), ['toggle' => true])
                ->assertOk();
        }
        // Una risposta registrata
        $this->actingAs($viewer)
            ->postJson(route('study.flag', ['question' => $questions->first()->id]), ['answer' => '1'])
            ->assertOk();

        $response = $this->actingAs($viewer)->get(route('study.summary'));

        $response->assertOk()
            ->assertViewIs('study.summary')
            ->assertViewHas('summary', function ($summary) use ($flagged) {
                return $summary['total'] === 4
                    && $summary['flagged_count'] === 2
                    && $summary['answered'] === 1
                    && $summary['flagged']->pluck('id')->sort()->values()->all()
                       === $flagged->pluck('id')->sort()->values()->all();
            });
    }

    public function test_summary_redirects_when_no_session(): void
    {
        $this->actingAs($this->viewer())
            ->get(route('study.summary'))
            ->assertRedirect(route('study.index'));
    }

    public function test_destroy_clears_session_and_redirects(): void
    {
        ['quiz' => $quiz] = $this->publishedQuizWithQuestions(2);
        $viewer           = $this->viewer();

        $this->actingAs($viewer)->post(route('study.start'), [
            'source'  => StudyService::SOURCE_QUIZ,
            'quiz_id' => $quiz->id,
        ]);

        $this->actingAs($viewer)
            ->delete(route('study.destroy'))
            ->assertRedirect(route('study.index'));

        $this->assertNull(session(StudyService::KEY_QUESTIONS));
        $this->assertNull(session(StudyService::KEY_INDEX));
        $this->assertNull(session(StudyService::KEY_FLAGGED));
    }

    public function test_flagged_source_restarts_session_with_marked_questions(): void
    {
        ['quiz' => $quiz, 'questions' => $questions] = $this->publishedQuizWithQuestions(4);
        $viewer = $this->viewer();
        $marked = $questions->take(2);

        $this->actingAs($viewer)->post(route('study.start'), [
            'source'  => StudyService::SOURCE_QUIZ,
            'quiz_id' => $quiz->id,
        ]);

        foreach ($marked as $q) {
            $this->actingAs($viewer)
                ->postJson(route('study.flag', ['question' => $q->id]), ['toggle' => true]);
        }

        $this->actingAs($viewer)
            ->post(route('study.start'), ['source' => StudyService::SOURCE_FLAGGED])
            ->assertRedirect(route('study.play'));

        $this->assertSame(
            $marked->pluck('id')->sort()->values()->all(),
            collect(session(StudyService::KEY_QUESTIONS))->sort()->values()->all()
        );
    }
}
