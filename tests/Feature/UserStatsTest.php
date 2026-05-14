<?php

namespace Tests\Feature;

use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\User;
use App\Services\UserStatsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class UserStatsTest extends TestCase
{
    use RefreshDatabase;

    private function makeAttempts(User $user, Quiz $quiz, array $rows): void
    {
        foreach ($rows as $row) {
            QuizAttempt::factory()->create(array_merge([
                'user_id' => $user->id,
                'quiz_id' => $quiz->id,
                'total_questions' => 10,
                'duration' => 120,
            ], $row));
        }
    }

    public function test_user_can_view_own_stats_dashboard(): void
    {
        $user = User::factory()->create();
        $quiz = Quiz::factory()->create(['title' => 'Quiz Demo']);

        $this->makeAttempts($user, $quiz, [
            ['score' => 8, 'created_at' => now()->subDays(2)],  // 80% pass
            ['score' => 4, 'created_at' => now()->subDays(1)],  // 40% fail
            ['score' => 10, 'created_at' => now()],             // 100% pass
        ]);

        $response = $this->actingAs($user)->get(route('stats.me'));

        $response->assertOk();
        $response->assertSee('Le mie statistiche');
        $response->assertSee('Quiz Demo');
        $response->assertSee('Tentativi totali');
        // 3 tentativi totali
        $response->assertSeeText('3');
    }

    public function test_empty_state_when_user_has_no_attempts(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('stats.me'));

        $response->assertOk();
        $response->assertSee('Nessun tentativo registrato');
    }

    public function test_stats_service_computes_correct_aggregates(): void
    {
        $user = User::factory()->create();
        $quiz = Quiz::factory()->create();

        $this->makeAttempts($user, $quiz, [
            ['score' => 8, 'total_questions' => 10],   // 80% pass
            ['score' => 5, 'total_questions' => 10],   // 50% fail
            ['score' => 10, 'total_questions' => 10],  // 100% pass
            ['score' => 6, 'total_questions' => 10],   // 60% pass (>= 60)
        ]);

        $service = app(UserStatsService::class);
        $stats = $service->get($user);

        $this->assertSame(4, $stats['total_attempts']);
        $this->assertSame(40, $stats['total_questions']);
        $this->assertSame(29, $stats['total_correct']);
        $this->assertSame(72.5, $stats['avg_percentage']);
        $this->assertSame(100.0, $stats['best_percentage']);
        $this->assertSame(50.0, $stats['worst_percentage']);
        $this->assertSame(3, $stats['passed_count']);
        $this->assertSame(1, $stats['failed_count']);
        $this->assertSame(75.0, $stats['pass_rate']);
    }

    public function test_stats_are_cached(): void
    {
        $user = User::factory()->create();
        $quiz = Quiz::factory()->create();

        $this->makeAttempts($user, $quiz, [
            ['score' => 7],
        ]);

        $service = app(UserStatsService::class);

        // Pre-warm cache
        $first = $service->get($user);
        $this->assertTrue(Cache::has(UserStatsService::cacheKey($user->id)));

        // Aggiunta tentativo direttamente in DB SENZA invalidare (bypass evento)
        QuizAttempt::query()->insert([
            'user_id' => $user->id,
            'quiz_id' => $quiz->id,
            'score' => 10,
            'total_questions' => 10,
            'duration' => 60,
            'answers' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $second = $service->get($user);

        // Stesso risultato → cache hit
        $this->assertSame($first['total_attempts'], $second['total_attempts']);
        $this->assertSame(1, $second['total_attempts']);
    }

    public function test_cache_is_invalidated_when_attempt_saved(): void
    {
        $user = User::factory()->create();
        $quiz = Quiz::factory()->create();

        $this->makeAttempts($user, $quiz, [
            ['score' => 7],
        ]);

        $service = app(UserStatsService::class);
        $service->get($user);
        $this->assertTrue(Cache::has(UserStatsService::cacheKey($user->id)));

        // Creazione tramite model → trigger evento saved
        QuizAttempt::factory()->create([
            'user_id' => $user->id,
            'quiz_id' => $quiz->id,
            'score' => 9,
            'total_questions' => 10,
        ]);

        $this->assertFalse(Cache::has(UserStatsService::cacheKey($user->id)));

        $refreshed = $service->get($user);
        $this->assertSame(2, $refreshed['total_attempts']);
    }

    public function test_admin_can_view_other_user_stats(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $target = User::factory()->create(['name' => 'Mario Rossi']);
        $quiz = Quiz::factory()->create(['title' => 'Quiz Admin Test']);

        $this->makeAttempts($target, $quiz, [
            ['score' => 8],
        ]);

        $response = $this->actingAs($admin)->get(route('admin.users.stats', $target));

        $response->assertOk();
        $response->assertSee('Statistiche di Mario Rossi');
        $response->assertSee('Quiz Admin Test');
        $response->assertSee('Torna agli utenti');
    }

    public function test_non_admin_cannot_view_other_user_stats(): void
    {
        $viewer = User::factory()->create(['role' => User::ROLE_VIEWER]);
        $target = User::factory()->create();

        $response = $this->actingAs($viewer)->get(route('admin.users.stats', $target));

        $response->assertForbidden();
    }

    public function test_guest_is_redirected_from_stats(): void
    {
        $response = $this->get(route('stats.me'));

        $response->assertRedirect(route('login'));
    }

    public function test_refresh_forces_cache_invalidation(): void
    {
        $user = User::factory()->create();
        $quiz = Quiz::factory()->create();

        $this->makeAttempts($user, $quiz, [
            ['score' => 5],
        ]);

        $service = app(UserStatsService::class);
        $service->get($user);
        $this->assertTrue(Cache::has(UserStatsService::cacheKey($user->id)));

        $response = $this->actingAs($user)->post(route('stats.refresh', $user));

        $response->assertRedirect(route('stats.me'));
        $this->assertFalse(Cache::has(UserStatsService::cacheKey($user->id)));
    }

    public function test_admin_users_index_shows_stats_button(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $target = User::factory()->create(['name' => 'Luigi Verdi']);

        $response = $this->actingAs($admin)->get(route('admin.users.index'));

        $response->assertOk();
        $response->assertSee(route('admin.users.stats', $target), false);
    }
}
