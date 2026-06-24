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
            // Feature 13.2 — Feature toggles
            FeatureSettingSeeder::class,
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
            // Feature 6.6 — Assegnazione studenti all'istruttore
            InstructorStudentSeeder::class,
            // Feature 6.8 — Note istruttore per studente (dipende da InstructorStudentSeeder)
            InstructorNoteSeeder::class,
            // Feature 9.0 — Sessioni guida pratica (dipende da InstructorStudentSeeder + DrivingModuleSeeder)
            DrivingSessionSeeder::class,
            // Contenuti studio per categoria con tracking letture
            StudyContentSeeder::class,
            // Segnalibri domande per i viewer
            BookmarkSeeder::class,
            // Materiali (link/note) per categorie
            CategoryMaterialSeeder::class,
            // Test diagnostico e piano di studio (Feature viewer)
            DiagnosticResultSeeder::class,
            // Notifiche DB (badge, esito esame, richieste anagrafica)
            NotificationSeeder::class,
            // Fix ordine: patente B ai viewer senza licenza + sync categorie-patenti
            DemoFixSeeder::class,
        ]);
    }
}
