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
            // Feature 11.0 — System settings (deve girare prima di qualsiasi seeder che legge i setting)
            SystemSettingSeeder::class,
            RolePermissionSeeder::class,
            AdminUserSeeder::class,
            UserSeeder::class,
            LicenseTypeSeeder::class,
            // Feature 9.0 — Moduli guida pratica
            DrivingModuleSeeder::class,
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
