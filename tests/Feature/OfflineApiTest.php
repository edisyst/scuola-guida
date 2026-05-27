<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Question;
use App\Models\QuestionReview;
use App\Models\User;
use App\Models\UserActivityLog;
use App\Services\BadgeService;
use App\Services\SpacedRepetitionService;
use App\Services\StreakService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class OfflineApiTest extends TestCase
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

    private function question(array $attrs = []): Question
    {
        $category = Category::factory()->create();
        return Question::factory()->create(array_merge(['category_id' => $category->id], $attrs));
    }

    // ──────────────────────────────────────────────────────────────────────────
    // GET /api/offline/questions — authentication & authorisation
    // ──────────────────────────────────────────────────────────────────────────

    public function test_questions_endpoint_requires_authentication(): void
    {
        $this->getJson('/api/offline/questions')
            ->assertStatus(401);
    }

    public function test_questions_endpoint_rejects_admin(): void
    {
        $this->actingAs($this->admin())
            ->getJson('/api/offline/questions')
            ->assertStatus(403);
    }

    public function test_questions_endpoint_rejects_editor(): void
    {
        $editor = User::factory()->create(['role' => User::ROLE_EDITOR]);

        $this->actingAs($editor)
            ->getJson('/api/offline/questions')
            ->assertStatus(403);
    }

    public function test_questions_endpoint_accessible_by_viewer(): void
    {
        $user = $this->viewer();

        $this->actingAs($user)
            ->getJson('/api/offline/questions')
            ->assertOk()
            ->assertJsonStructure(['questions', 'count', 'fetched_at']);
    }

    public function test_questions_endpoint_returns_users_reviewed_questions(): void
    {
        $user     = $this->viewer();
        $question = $this->question();

        QuestionReview::factory()->create([
            'user_id'          => $user->id,
            'question_id'      => $question->id,
            'last_reviewed_at' => now()->subHour(),
        ]);

        $response = $this->actingAs($user)
            ->getJson('/api/offline/questions')
            ->assertOk();

        $questions = $response->json('questions');
        $this->assertCount(1, $questions);
        $this->assertEquals($question->id, $questions[0]['id']);
        $this->assertArrayHasKey('question', $questions[0]);
        $this->assertArrayHasKey('is_true', $questions[0]);
        $this->assertArrayHasKey('category', $questions[0]);
    }

    public function test_questions_endpoint_does_not_return_other_users_questions(): void
    {
        $user1 = $this->viewer();
        $user2 = $this->viewer();
        $q     = $this->question();

        QuestionReview::factory()->create(['user_id' => $user2->id, 'question_id' => $q->id]);

        $response = $this->actingAs($user1)
            ->getJson('/api/offline/questions')
            ->assertOk();

        $this->assertCount(0, $response->json('questions'));
    }

    // ──────────────────────────────────────────────────────────────────────────
    // GET /api/offline/questions — throttle
    // ──────────────────────────────────────────────────────────────────────────

    public function test_questions_endpoint_throttle_allows_first_request(): void
    {
        $this->actingAs($this->viewer())
            ->getJson('/api/offline/questions')
            ->assertOk();
    }

    public function test_questions_endpoint_throttle_blocks_second_consecutive_request(): void
    {
        $user = $this->viewer();

        // First request exhausts the 1-per-5-min quota
        $this->actingAs($user)->getJson('/api/offline/questions')->assertOk();

        // Second request within the same window must be rate-limited
        $this->actingAs($user)
            ->getJson('/api/offline/questions')
            ->assertStatus(429);
    }

    // ──────────────────────────────────────────────────────────────────────────
    // POST /api/offline/sync-answers — authentication & authorisation
    // ──────────────────────────────────────────────────────────────────────────

    public function test_sync_answers_requires_authentication(): void
    {
        $this->postJson('/api/offline/sync-answers', ['answers' => []])
            ->assertStatus(401);
    }

    public function test_sync_answers_rejects_non_viewer(): void
    {
        $q = $this->question();

        $this->actingAs($this->admin())
            ->postJson('/api/offline/sync-answers', ['answers' => [
                ['id' => 1, 'question_id' => $q->id, 'user_answer' => 1, 'is_correct' => true, 'answered_at' => now()->toISOString()],
            ]])
            ->assertStatus(403);
    }

    // ──────────────────────────────────────────────────────────────────────────
    // POST /api/offline/sync-answers — validation
    // ──────────────────────────────────────────────────────────────────────────

    public function test_sync_answers_validates_missing_answers(): void
    {
        $this->actingAs($this->viewer())
            ->postJson('/api/offline/sync-answers', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['answers']);
    }

    public function test_sync_answers_validates_question_exists(): void
    {
        $this->actingAs($this->viewer())
            ->postJson('/api/offline/sync-answers', [
                'answers' => [
                    ['id' => 1, 'question_id' => 99999, 'user_answer' => 1, 'is_correct' => true, 'answered_at' => now()->toISOString()],
                ],
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['answers.0.question_id']);
    }

    // ──────────────────────────────────────────────────────────────────────────
    // POST /api/offline/sync-answers — service invocation
    // ──────────────────────────────────────────────────────────────────────────

    public function test_sync_answers_calls_spaced_repetition_for_each_answer(): void
    {
        $user = $this->viewer();
        $q1   = $this->question(['is_true' => 1]);
        $q2   = $this->question(['is_true' => 0]);

        $srMock = Mockery::mock(SpacedRepetitionService::class);
        $srMock->shouldReceive('recordAnswer')
            ->twice()
            ->withArgs(fn ($u, $qId, $correct) => $u->id === $user->id);

        $this->app->instance(SpacedRepetitionService::class, $srMock);

        // StreakService and BadgeService — allow any calls
        $this->app->instance(StreakService::class, Mockery::mock(StreakService::class)->shouldIgnoreMissing());
        $this->app->instance(BadgeService::class, Mockery::mock(BadgeService::class)->shouldIgnoreMissing());

        $this->actingAs($user)
            ->postJson('/api/offline/sync-answers', [
                'answers' => [
                    ['id' => 1, 'question_id' => $q1->id, 'user_answer' => 1, 'is_correct' => true,  'answered_at' => now()->toISOString()],
                    ['id' => 2, 'question_id' => $q2->id, 'user_answer' => 0, 'is_correct' => false, 'answered_at' => now()->toISOString()],
                ],
            ])
            ->assertOk();
    }

    public function test_sync_answers_calls_streak_service_once_per_sync(): void
    {
        $user = $this->viewer();
        $q1   = $this->question();
        $q2   = $this->question();

        $streakMock = Mockery::mock(StreakService::class);
        $streakMock->shouldReceive('recordActivity')->once()->with(Mockery::on(fn ($u) => $u->id === $user->id));

        $this->app->instance(StreakService::class, $streakMock);
        $this->app->instance(SpacedRepetitionService::class, Mockery::mock(SpacedRepetitionService::class)->shouldIgnoreMissing());
        $this->app->instance(BadgeService::class, Mockery::mock(BadgeService::class)->shouldIgnoreMissing());

        $this->actingAs($user)
            ->postJson('/api/offline/sync-answers', [
                'answers' => [
                    ['id' => 1, 'question_id' => $q1->id, 'user_answer' => 1, 'is_correct' => true,  'answered_at' => now()->toISOString()],
                    ['id' => 2, 'question_id' => $q2->id, 'user_answer' => 0, 'is_correct' => false, 'answered_at' => now()->toISOString()],
                ],
            ])
            ->assertOk();
    }

    // ──────────────────────────────────────────────────────────────────────────
    // POST /api/offline/sync-answers — DB side effects
    // ──────────────────────────────────────────────────────────────────────────

    public function test_sync_answers_updates_question_reviews_table(): void
    {
        $user = $this->viewer();
        $q    = $this->question(['is_true' => 1]);

        $this->actingAs($user)
            ->postJson('/api/offline/sync-answers', [
                'answers' => [
                    ['id' => 1, 'question_id' => $q->id, 'user_answer' => 1, 'is_correct' => true, 'answered_at' => now()->toISOString()],
                ],
            ])
            ->assertOk()
            ->assertJson(['count' => 1]);

        $this->assertDatabaseHas('question_reviews', [
            'user_id'     => $user->id,
            'question_id' => $q->id,
        ]);
    }

    public function test_sync_answers_updates_user_activity_log(): void
    {
        $user = $this->viewer();
        $q    = $this->question();

        $this->actingAs($user)
            ->postJson('/api/offline/sync-answers', [
                'answers' => [
                    ['id' => 1, 'question_id' => $q->id, 'user_answer' => 1, 'is_correct' => true, 'answered_at' => now()->toISOString()],
                ],
            ])
            ->assertOk();

        $this->assertDatabaseHas('user_activity_log', [
            'user_id'       => $user->id,
            'activity_date' => now()->format('Y-m-d'),
        ]);
    }

    public function test_sync_answers_returns_synced_ids(): void
    {
        $user = $this->viewer();
        $q    = $this->question();

        $response = $this->actingAs($user)
            ->postJson('/api/offline/sync-answers', [
                'answers' => [
                    ['id' => 42, 'question_id' => $q->id, 'user_answer' => 1, 'is_correct' => true, 'answered_at' => now()->toISOString()],
                ],
            ])
            ->assertOk();

        $this->assertContains(42, $response->json('synced_ids'));
    }

    // ──────────────────────────────────────────────────────────────────────────
    // GET /offline — public page
    // ──────────────────────────────────────────────────────────────────────────

    public function test_offline_page_accessible_without_auth(): void
    {
        $this->get('/offline')->assertOk()->assertSee('Sei offline');
    }
}
