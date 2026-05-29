<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserActivityLog;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

class StreakService
{
    public static function streakCacheKey(int $userId): string
    {
        return "streak_{$userId}";
    }

    /**
     * Statistiche streak complete per la dashboard viewer.
     * TTL dinamico fino a mezzanotte: lo streak è day-relative e i dati
     * cambiano al cambio del giorno o quando recordActivity() invalida la chiave.
     *
     * @return array{current: int, longest: int, has_today: bool}
     */
    public function getStats(User $user): array
    {
        $ttl = (int) now()->endOfDay()->diffInSeconds();

        return Cache::remember(self::streakCacheKey($user->id), $ttl, function () use ($user) {
            return [
                'current'   => $this->getCurrentStreak($user),
                'longest'   => $this->getLongestStreak($user),
                'has_today' => UserActivityLog::where('user_id', $user->id)
                    ->where('activity_date', Carbon::today()->toDateString())
                    ->exists(),
            ];
        });
    }

    public function recordActivity(User $user): void
    {
        $today = Carbon::today()->toDateString();

        $existing = UserActivityLog::where('user_id', $user->id)
            ->where('activity_date', $today)
            ->first();

        if ($existing) {
            $existing->increment('actions_count');
        } else {
            UserActivityLog::create([
                'user_id'       => $user->id,
                'activity_date' => $today,
                'actions_count' => 1,
            ]);
        }

        Cache::forget(self::streakCacheKey($user->id));
    }

    public function getCurrentStreak(User $user): int
    {
        $today = Carbon::today();

        $hasToday = UserActivityLog::where('user_id', $user->id)
            ->where('activity_date', $today->toDateString())
            ->exists();

        $checkFrom = $hasToday ? $today->copy() : $today->copy()->subDay();

        $logs = UserActivityLog::where('user_id', $user->id)
            ->where('activity_date', '<=', $checkFrom->toDateString())
            ->orderByDesc('activity_date')
            ->pluck('activity_date');

        if ($logs->isEmpty()) {
            return 0;
        }

        $streak   = 0;
        $expected = $checkFrom->copy();

        foreach ($logs as $dateString) {
            $date = Carbon::parse($dateString);
            if ($date->isSameDay($expected)) {
                $streak++;
                $expected->subDay();
            } else {
                break;
            }
        }

        return $streak;
    }

    public function getLongestStreak(User $user): int
    {
        $logs = UserActivityLog::where('user_id', $user->id)
            ->orderBy('activity_date')
            ->pluck('activity_date');

        if ($logs->isEmpty()) {
            return 0;
        }

        $longest = 1;
        $current = 1;

        for ($i = 1; $i < $logs->count(); $i++) {
            $prev = Carbon::parse($logs[$i - 1]);
            $curr = Carbon::parse($logs[$i]);

            if ($curr->diffInDays($prev) === 1) {
                $current++;
                if ($current > $longest) {
                    $longest = $current;
                }
            } else {
                $current = 1;
            }
        }

        return $longest;
    }
}
