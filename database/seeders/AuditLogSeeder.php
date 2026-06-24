<?php

namespace Database\Seeders;

use App\Models\AuditLog;
use App\Models\Question;
use App\Models\Quiz;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AuditLogSeeder extends Seeder
{
    public function run(): void
    {
        // Evita duplicazione su re-run: se esistono già entry con user_id valorizzato, salta
        if (AuditLog::whereNotNull('user_id')->count() > 30) {
            $this->command->warn('AuditLogSeeder: dati già presenti, saltato.');
            return;
        }

        $editors     = User::whereIn('role', [User::ROLE_ADMIN, User::ROLE_EDITOR])->get();
        $questionIds = Question::pluck('id')->toArray();
        $quizzes     = Quiz::all();

        if ($editors->isEmpty() || empty($questionIds)) {
            $this->command->warn('Nessun editor o domanda: AuditLogSeeder saltato.');
            return;
        }

        $admin = $editors->firstWhere('role', User::ROLE_ADMIN) ?? $editors->first();
        $total = 0;

        // Per ogni editor/admin: domande create + domande aggiornate su 60 giorni
        foreach ($editors as $editor) {
            $createdIds = collect($questionIds)->shuffle()->take(fake()->numberBetween(5, 15));
            foreach ($createdIds as $qId) {
                $createdAt = now()->subDays(fake()->numberBetween(0, 60));
                $demoQuestions = [
                    'Il conducente deve cedere la precedenza ai veicoli provenienti da destra.',
                    'In autostrada è obbligatorio percorrere la corsia di destra.',
                    'Il limite di velocità in area urbana è di 50 km/h.',
                    'Il sorpasso è sempre vietato in prossimità di un incrocio.',
                    'Le cinture di sicurezza sono obbligatorie per tutti gli occupanti.',
                    'Il segnale di Stop impone la fermata obbligatoria.',
                    'Con pioggia il limite in autostrada scende a 110 km/h.',
                    'I pneumatici invernali vanno montati dal 15 novembre al 15 aprile.',
                ];

                AuditLog::insert([
                    'user_id'    => $editor->id,
                    'event'      => 'created',
                    'model_type' => Question::class,
                    'model_id'   => $qId,
                    'old_values' => null,
                    'new_values' => json_encode(['question' => fake()->randomElement($demoQuestions), 'is_true' => fake()->boolean()]),
                    'created_at' => $createdAt,
                    'updated_at' => $createdAt,
                ]);
                $total++;
            }

            $updatedIds = collect($questionIds)->shuffle()->take(fake()->numberBetween(5, 10));
            foreach ($updatedIds as $qId) {
                $updatedAt = now()->subDays(fake()->numberBetween(0, 60));
                AuditLog::insert([
                    'user_id'    => $editor->id,
                    'event'      => 'updated',
                    'model_type' => Question::class,
                    'model_id'   => $qId,
                    'old_values' => json_encode(['is_true' => true]),
                    'new_values' => json_encode(['is_true' => false]),
                    'created_at' => $updatedAt,
                    'updated_at' => $updatedAt,
                ]);
                $total++;
            }
        }

        // Transizioni quiz draft → published per la metrica quizzes_published
        if ($quizzes->isNotEmpty()) {
            foreach ($editors as $editor) {
                $subset = $quizzes->shuffle()->take(fake()->numberBetween(2, 4));
                foreach ($subset as $quiz) {
                    $publishedAt = now()->subDays(fake()->numberBetween(0, 30));
                    AuditLog::insert([
                        'user_id'    => $editor->id,
                        'event'      => 'updated',
                        'model_type' => Quiz::class,
                        'model_id'   => $quiz->id,
                        'old_values' => json_encode(['status' => Quiz::STATUS_DRAFT]),
                        'new_values' => json_encode(['status' => Quiz::STATUS_PUBLISHED]),
                        'created_at' => $publishedAt,
                        'updated_at' => $publishedAt,
                    ]);
                    $total++;
                }
            }

            // Marca metà dei quiz come confermati: aggiorna confirmed_by + confirmed_at
            $toConfirm = $quizzes->shuffle()->take((int) ($quizzes->count() / 2));
            foreach ($toConfirm as $quiz) {
                $confirmedAt = now()->subDays(fake()->numberBetween(1, 15));
                DB::table('quizzes')->where('id', $quiz->id)->update([
                    'status'       => Quiz::STATUS_CONFIRMED,
                    'confirmed_by' => $admin->id,
                    'confirmed_at' => $confirmedAt,
                    'updated_at'   => $confirmedAt,
                ]);
            }

            $this->command->info("CONFERMATI {$toConfirm->count()} quiz con confirmed_by/confirmed_at");
        }

        $this->command->info("CREATI {$total} RECORD AUDIT LOG (Feature 6.4 / 6.5)");
    }
}
