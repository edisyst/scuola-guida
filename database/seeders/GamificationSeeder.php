<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\UserActivityLog;
use App\Models\UserBadge;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class GamificationSeeder extends Seeder
{
    // Badge assegnabili ai viewer; streak_* anche ad admin/editor
    private const VIEWER_BADGES = [
        'streak_7', 'streak_30', 'questions_100', 'questions_500', 'first_pass', 'all_categories',
    ];

    private const STAFF_BADGES = [
        'streak_7', 'streak_30',
    ];

    public function run(): void
    {
        $users = User::all();

        if ($users->isEmpty()) {
            $this->command->warn('Nessun utente: GamificationSeeder saltato.');
            return;
        }

        $activityCount = 0;
        $badgeCount    = 0;

        foreach ($users as $user) {
            // Activity log: 70% di probabilità di attività per ogni giorno degli ultimi 60
            for ($daysAgo = 60; $daysAgo >= 0; $daysAgo--) {
                if (!fake()->boolean(70)) {
                    continue;
                }

                $day = Carbon::today()->subDays($daysAgo);

                UserActivityLog::insertOrIgnore([
                    'user_id'       => $user->id,
                    'activity_date' => $day->toDateString(),
                    'actions_count' => fake()->numberBetween(1, 15),
                    'created_at'    => $day,
                    'updated_at'    => $day,
                ]);
                $activityCount++;
            }

            // Badge: assegna un sottoinsieme casuale in base al ruolo
            $eligible  = $user->role === User::ROLE_VIEWER ? self::VIEWER_BADGES : self::STAFF_BADGES;
            $numBadges = fake()->numberBetween(0, (int) (count($eligible) * 0.6));
            $assigned  = collect($eligible)->shuffle()->take($numBadges);

            foreach ($assigned as $code) {
                $earnedAt = now()->subDays(fake()->numberBetween(1, 60));

                UserBadge::insertOrIgnore([
                    'user_id'    => $user->id,
                    'badge_code' => $code,
                    'earned_at'  => $earnedAt,
                    'metadata'   => null,
                    'created_at' => $earnedAt,
                    'updated_at' => $earnedAt,
                ]);
                $badgeCount++;
            }
        }

        $this->command->info("CREATI {$activityCount} RECORD ATTIVITÀ, {$badgeCount} BADGE (Feature 5.5)");
    }
}
