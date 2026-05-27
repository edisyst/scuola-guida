<?php

namespace App\Services;

use App\Models\Category;
use App\Models\QuizAttempt;
use App\Models\User;
use App\Models\UserBadge;
use App\Notifications\BadgeEarned;
use Illuminate\Support\Facades\DB;

class BadgeService
{
    public function __construct(private StreakService $streakService) {}

    public function awardIfEligible(User $user, string $badgeCode, array $metadata = []): ?UserBadge
    {
        if (!array_key_exists($badgeCode, config('badges', []))) {
            return null;
        }

        if (UserBadge::where('user_id', $user->id)->where('badge_code', $badgeCode)->exists()) {
            return null;
        }

        $badge = UserBadge::create([
            'user_id'    => $user->id,
            'badge_code' => $badgeCode,
            'earned_at'  => now(),
            'metadata'   => $metadata ?: null,
        ]);

        $user->notify(new BadgeEarned($badgeCode, $metadata));

        return $badge;
    }

    public function checkAllBadges(User $user): array
    {
        $earned = UserBadge::where('user_id', $user->id)
            ->pluck('badge_code')
            ->flip();

        $newBadges = [];

        // Streak badges
        $streak = null;

        foreach (['streak_7' => 7, 'streak_30' => 30, 'streak_100' => 100] as $code => $days) {
            if (isset($earned[$code])) {
                continue;
            }

            if ($streak === null) {
                $streak = $this->streakService->getCurrentStreak($user);
            }

            if ($streak >= $days) {
                $badge = $this->awardIfEligible($user, $code, ['streak_days' => $streak]);
                if ($badge) {
                    $newBadges[] = $code;
                }
            }
        }

        // Questions count badges
        $totalAnswered = null;

        foreach (['questions_100' => 100, 'questions_500' => 500, 'questions_1000' => 1000] as $code => $threshold) {
            if (isset($earned[$code])) {
                continue;
            }

            if ($totalAnswered === null) {
                $totalAnswered = (int) QuizAttempt::where('user_id', $user->id)->sum('total_questions');
            }

            if ($totalAnswered >= $threshold) {
                $badge = $this->awardIfEligible($user, $code, ['total_questions' => $totalAnswered]);
                if ($badge) {
                    $newBadges[] = $code;
                }
            }
        }

        // first_pass: simulator attempt with Promosso result
        if (!isset($earned['first_pass'])) {
            $maxErrors = (int) config('simulator.max_errors', 4);

            $passed = QuizAttempt::where('user_id', $user->id)
                ->whereNull('quiz_id')
                ->whereRaw('(total_questions - score) <= ?', [$maxErrors])
                ->first();

            if ($passed) {
                $badge = $this->awardIfEligible($user, 'first_pass', [
                    'score'          => $passed->score,
                    'total_questions' => $passed->total_questions,
                ]);
                if ($badge) {
                    $newBadges[] = 'first_pass';
                }
            }
        }

        // all_categories: at least one question answered per category
        if (!isset($earned['all_categories'])) {
            $totalCategories = Category::count();

            if ($totalCategories > 0) {
                $coveredCount = DB::table('question_reviews')
                    ->join('questions', 'question_reviews.question_id', '=', 'questions.id')
                    ->where('question_reviews.user_id', $user->id)
                    ->distinct()
                    ->count('questions.category_id');

                if ($coveredCount >= $totalCategories) {
                    $badge = $this->awardIfEligible($user, 'all_categories', [
                        'categories_covered' => $coveredCount,
                    ]);
                    if ($badge) {
                        $newBadges[] = 'all_categories';
                    }
                }
            }
        }

        return $newBadges;
    }
}
