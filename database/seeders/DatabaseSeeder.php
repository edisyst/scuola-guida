<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RolePermissionSeeder::class,
            AdminUserSeeder::class,
            UserSeeder::class,
            CategorySeeder::class,
            QuestionSeeder::class,
            QuizSeeder::class,
            QuizAttemptSeeder::class,
            // Feature 5.1 — Revisione errori
            LearnedQuestionsSeeder::class,
            // Feature 5.4 — Spaced repetition
            SpacedRepetitionSeeder::class,
            // Feature 5.5 — Gamification streak/badge
            GamificationSeeder::class,
            // Feature 6.2 — Versionamento domande
            QuestionVersionSeeder::class,
            // Feature 6.4 / 6.5 — Audit log + dashboard editor
            AuditLogSeeder::class,
            // Feature 6.5 — Segnalazioni per metriche globali
            QuestionReportSeeder::class,
        ]);
    }
}
