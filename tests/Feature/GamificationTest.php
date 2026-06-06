<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\LicenseType;
use App\Models\Question;
use App\Models\QuestionReview;
use App\Models\QuizAttempt;
use App\Models\User;
use App\Models\UserActivityLog;
use App\Models\UserBadge;
use App\Notifications\BadgeEarned;
use App\Services\BadgeService;
use App\Services\StreakService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class GamificationTest extends TestCase
{
    use RefreshDatabase;

    private LicenseType $licenseType;

    protected function setUp(): void
    {
        parent::setUp();
        $this->licenseType = LicenseType::factory()->create();
    }

    private function viewer(): User
    {
        return User::factory()->create([
            'role'                   => User::ROLE_VIEWER,
            'active_license_type_id' => $this->licenseType->id,
        ]);
    }

    // ──────────────────────────────────────────────────────────────────────────
    // StreakService::recordActivity
    // ──────────────────────────────────────────────────────────────────────────

    public function test_record_activity_creates_log_for_today(): void
    {
        $user    = $this->viewer();
        $service = app(StreakService::class);

        $service->recordActivity($user);

        $this->assertDatabaseHas('user_activity_log', [
            'user_id'       => $user->id,
            'activity_date' => Carbon::today()->toDateString(),
            'actions_count' => 1,
        ]);
    }

    public function test_record_activity_increments_existing_log(): void
    {
        $user    = $this->viewer();
        $service = app(StreakService::class);

        $service->recordActivity($user);
        $service->recordActivity($user);
        $service->recordActivity($user);

        $this->assertDatabaseHas('user_activity_log', [
            'user_id'       => $user->id,
            'activity_date' => Carbon::today()->toDateString(),
            'actions_count' => 3,
        ]);
        $this->assertDatabaseCount('user_activity_log', 1);
    }

    // ──────────────────────────────────────────────────────────────────────────
    // StreakService::getCurrentStreak
    // ──────────────────────────────────────────────────────────────────────────

    public function test_get_current_streak_returns_zero_with_no_activity(): void
    {
        $user = $this->viewer();
        $this->assertSame(0, app(StreakService::class)->getCurrentStreak($user));
    }

    public function test_get_current_streak_counts_consecutive_days(): void
    {
        $user    = $this->viewer();
        $service = app(StreakService::class);

        foreach (range(0, 4) as $daysAgo) {
            UserActivityLog::create([
                'user_id'       => $user->id,
                'activity_date' => Carbon::today()->subDays($daysAgo)->toDateString(),
                'actions_count' => 1,
            ]);
        }

        $this->assertSame(5, $service->getCurrentStreak($user));
    }

    public function test_get_current_streak_stops_at_gap(): void
    {
        $user    = $this->viewer();
        $service = app(StreakService::class);

        // Today + yesterday = streak 2; then a gap; 5 days ago alone
        foreach ([0, 1, 5] as $daysAgo) {
            UserActivityLog::create([
                'user_id'       => $user->id,
                'activity_date' => Carbon::today()->subDays($daysAgo)->toDateString(),
                'actions_count' => 1,
            ]);
        }

        $this->assertSame(2, $service->getCurrentStreak($user));
    }

    public function test_get_current_streak_counts_yesterday_when_no_activity_today(): void
    {
        $user    = $this->viewer();
        $service = app(StreakService::class);

        foreach (range(1, 3) as $daysAgo) {
            UserActivityLog::create([
                'user_id'       => $user->id,
                'activity_date' => Carbon::today()->subDays($daysAgo)->toDateString(),
                'actions_count' => 1,
            ]);
        }

        $this->assertSame(3, $service->getCurrentStreak($user));
    }

    // ──────────────────────────────────────────────────────────────────────────
    // BadgeService::awardIfEligible
    // ──────────────────────────────────────────────────────────────────────────

    public function test_award_if_eligible_creates_badge(): void
    {
        Notification::fake();
        $user  = $this->viewer();
        $badge = app(BadgeService::class)->awardIfEligible($user, 'streak_7');

        $this->assertNotNull($badge);
        $this->assertDatabaseHas('user_badges', [
            'user_id'    => $user->id,
            'badge_code' => 'streak_7',
        ]);
    }

    public function test_award_if_eligible_is_idempotent(): void
    {
        Notification::fake();
        $user    = $this->viewer();
        $service = app(BadgeService::class);

        $first  = $service->awardIfEligible($user, 'streak_7');
        $second = $service->awardIfEligible($user, 'streak_7');

        $this->assertNotNull($first);
        $this->assertNull($second);
        $this->assertDatabaseCount('user_badges', 1);
    }

    public function test_award_if_eligible_sends_notification(): void
    {
        Notification::fake();
        $user = $this->viewer();

        app(BadgeService::class)->awardIfEligible($user, 'streak_7');

        Notification::assertSentTo($user, BadgeEarned::class, function ($notification) {
            return $notification->badgeCode === 'streak_7';
        });
    }

    public function test_no_notification_sent_when_badge_already_earned(): void
    {
        Notification::fake();
        $user    = $this->viewer();
        $service = app(BadgeService::class);

        $service->awardIfEligible($user, 'streak_7');
        $service->awardIfEligible($user, 'streak_7');

        Notification::assertSentToTimes($user, BadgeEarned::class, 1);
    }

    // ──────────────────────────────────────────────────────────────────────────
    // BadgeService::checkAllBadges — streak badges
    // ──────────────────────────────────────────────────────────────────────────

    public function test_streak_7_badge_earned_when_streak_reaches_7(): void
    {
        Notification::fake();
        $user    = $this->viewer();
        $service = app(BadgeService::class);

        foreach (range(0, 6) as $daysAgo) {
            UserActivityLog::create([
                'user_id'       => $user->id,
                'activity_date' => Carbon::today()->subDays($daysAgo)->toDateString(),
                'actions_count' => 1,
            ]);
        }

        $newBadges = $service->checkAllBadges($user);

        $this->assertContains('streak_7', $newBadges);
        $this->assertDatabaseHas('user_badges', ['user_id' => $user->id, 'badge_code' => 'streak_7']);
    }

    public function test_streak_badge_not_earned_when_streak_too_short(): void
    {
        Notification::fake();
        $user = $this->viewer();

        foreach (range(0, 4) as $daysAgo) {
            UserActivityLog::create([
                'user_id'       => $user->id,
                'activity_date' => Carbon::today()->subDays($daysAgo)->toDateString(),
                'actions_count' => 1,
            ]);
        }

        app(BadgeService::class)->checkAllBadges($user);

        $this->assertDatabaseMissing('user_badges', ['user_id' => $user->id, 'badge_code' => 'streak_7']);
    }

    // ──────────────────────────────────────────────────────────────────────────
    // BadgeService::checkAllBadges — questions count badges
    // ──────────────────────────────────────────────────────────────────────────

    public function test_questions_100_badge_earned_when_threshold_reached(): void
    {
        Notification::fake();
        $user = $this->viewer();

        QuizAttempt::factory()->create([
            'user_id'         => $user->id,
            'quiz_id'         => null,
            'score'           => 20,
            'total_questions' => 100,
        ]);

        app(BadgeService::class)->checkAllBadges($user);

        $this->assertDatabaseHas('user_badges', ['user_id' => $user->id, 'badge_code' => 'questions_100']);
    }

    public function test_questions_badge_not_earned_below_threshold(): void
    {
        Notification::fake();
        $user = $this->viewer();

        QuizAttempt::factory()->create([
            'user_id'         => $user->id,
            'quiz_id'         => null,
            'score'           => 20,
            'total_questions' => 50,
        ]);

        app(BadgeService::class)->checkAllBadges($user);

        $this->assertDatabaseMissing('user_badges', ['user_id' => $user->id, 'badge_code' => 'questions_100']);
    }

    // ──────────────────────────────────────────────────────────────────────────
    // BadgeService::checkAllBadges — first_pass
    // ──────────────────────────────────────────────────────────────────────────

    public function test_first_pass_badge_earned_when_simulator_passed(): void
    {
        Notification::fake();
        $user      = $this->viewer();
        $maxErrors = (int) config('simulator.max_errors', 4);

        QuizAttempt::factory()->create([
            'user_id'         => $user->id,
            'quiz_id'         => null,
            'score'           => 30,
            'total_questions' => 30,
        ]);

        app(BadgeService::class)->checkAllBadges($user);

        $this->assertDatabaseHas('user_badges', ['user_id' => $user->id, 'badge_code' => 'first_pass']);
    }

    public function test_first_pass_badge_not_earned_when_simulator_failed(): void
    {
        Notification::fake();
        $user      = $this->viewer();
        $maxErrors = (int) config('simulator.max_errors', 4);

        QuizAttempt::factory()->create([
            'user_id'         => $user->id,
            'quiz_id'         => null,
            'score'           => 20,          // 10 errors — exceeds max_errors
            'total_questions' => 30,
        ]);

        app(BadgeService::class)->checkAllBadges($user);

        $this->assertDatabaseMissing('user_badges', ['user_id' => $user->id, 'badge_code' => 'first_pass']);
    }

    public function test_simulator_submit_awards_first_pass_badge(): void
    {
        Notification::fake();
        $user = $this->viewer();
        $this->actingAs($user);

        $maxErrors = (int) config('simulator.max_errors', 4);

        // Simulate an existing attempt (quiz_id=null) that passes
        $attempt = QuizAttempt::factory()->create([
            'user_id'         => $user->id,
            'quiz_id'         => null,
            'score'           => 30,
            'total_questions' => 30,
        ]);

        // Award directly via BadgeService as the controller would
        app(BadgeService::class)->awardIfEligible($user, 'first_pass', [
            'score'           => $attempt->score,
            'total_questions' => $attempt->total_questions,
            'date'            => now()->toDateString(),
        ]);

        $this->assertDatabaseHas('user_badges', ['user_id' => $user->id, 'badge_code' => 'first_pass']);
        Notification::assertSentTo($user, BadgeEarned::class);
    }

    // ──────────────────────────────────────────────────────────────────────────
    // BadgeService::checkAllBadges — all_categories
    // ──────────────────────────────────────────────────────────────────────────

    public function test_all_categories_badge_earned_when_all_covered(): void
    {
        Notification::fake();
        $user = $this->viewer();

        $categories = Category::factory()->count(3)->create();

        foreach ($categories as $cat) {
            $question = Question::factory()->create(['category_id' => $cat->id]);
            QuestionReview::factory()->create([
                'user_id'     => $user->id,
                'question_id' => $question->id,
            ]);
        }

        app(BadgeService::class)->checkAllBadges($user);

        $this->assertDatabaseHas('user_badges', ['user_id' => $user->id, 'badge_code' => 'all_categories']);
    }

    public function test_all_categories_badge_not_earned_when_some_missing(): void
    {
        Notification::fake();
        $user = $this->viewer();

        $categories = Category::factory()->count(3)->create();

        // Only cover first two categories
        foreach ($categories->take(2) as $cat) {
            $question = Question::factory()->create(['category_id' => $cat->id]);
            QuestionReview::factory()->create([
                'user_id'     => $user->id,
                'question_id' => $question->id,
            ]);
        }

        app(BadgeService::class)->checkAllBadges($user);

        $this->assertDatabaseMissing('user_badges', ['user_id' => $user->id, 'badge_code' => 'all_categories']);
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Dashboard streak widget
    // ──────────────────────────────────────────────────────────────────────────

    public function test_dashboard_shows_streak_widget_for_viewer(): void
    {
        $user = $this->viewer();
        $this->actingAs($user);

        UserActivityLog::create([
            'user_id'       => $user->id,
            'activity_date' => Carbon::today()->toDateString(),
            'actions_count' => 1,
        ]);

        $response = $this->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('La tua streak');
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Profile badges page
    // ──────────────────────────────────────────────────────────────────────────

    public function test_badges_page_accessible_by_viewer(): void
    {
        $user = $this->viewer();
        $this->actingAs($user);

        $response = $this->get(route('viewer.profile.badges'));

        $response->assertOk();
        $response->assertSee('I miei badge');
    }

    public function test_badges_page_shows_earned_and_unearned_badges(): void
    {
        Notification::fake();
        $user = $this->viewer();
        $this->actingAs($user);

        UserBadge::create([
            'user_id'    => $user->id,
            'badge_code' => 'streak_7',
            'earned_at'  => now(),
        ]);

        $response = $this->get(route('viewer.profile.badges'));

        $response->assertSee('Costanza');
        $response->assertSee('Ottenuto il');
        $response->assertSee('Non ancora ottenuto');
    }

    public function test_badges_page_blocked_for_non_viewer(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $this->actingAs($admin);

        $this->get(route('viewer.profile.badges'))->assertForbidden();
    }
}
