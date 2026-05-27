<?php

namespace App\Http\Controllers\Viewer;

use App\Http\Controllers\Controller;
use App\Models\UserBadge;
use App\Services\StreakService;

class ProfileBadgesController extends Controller
{
    public function __construct(private StreakService $streakService) {}

    public function index()
    {
        abort_unless(auth()->user()->isViewer(), 403);

        $user = auth()->user();

        $earnedBadges = UserBadge::where('user_id', $user->id)
            ->orderByDesc('earned_at')
            ->get()
            ->keyBy('badge_code');

        $allBadges     = config('badges', []);
        $currentStreak = $this->streakService->getCurrentStreak($user);
        $longestStreak = $this->streakService->getLongestStreak($user);

        return view('viewer.badges', compact(
            'earnedBadges',
            'allBadges',
            'currentStreak',
            'longestStreak',
        ));
    }
}
