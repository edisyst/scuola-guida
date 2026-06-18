<?php

namespace Database\Seeders;

use App\Models\DrivingModule;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class DrivingSessionSeeder extends Seeder
{
    private const NOTES = [
        null,
        'Ottima padronanza del volante.',
        'Difficoltà iniziali superate durante la sessione.',
        'Buona progressione rispetto alla lezione precedente.',
        'Pronto per il percorso successivo.',
        'Da ripassare: distanza di sicurezza in coda.',
        'Sessione svolta in condizioni di pioggia leggera.',
        'Ha gestito bene il traffico urbano.',
    ];

    public function run(): void
    {
        $instructor = User::where('role', User::ROLE_INSTRUCTOR)->first();
        $admin      = User::where('role', User::ROLE_ADMIN)->first();
        $modules    = DrivingModule::all();

        if ($modules->isEmpty()) {
            $this->command->warn('Nessun modulo guida trovato: DrivingSessionSeeder saltato. Eseguire DrivingModuleSeeder prima.');
            return;
        }

        $studentIds = DB::table('instructor_student')
            ->where('instructor_id', $instructor?->id)
            ->pluck('student_id');

        if ($studentIds->isEmpty()) {
            // Fallback: prendi i primi 5 viewer anche senza assegnazione istruttore
            $studentIds = User::where('role', User::ROLE_VIEWER)->take(5)->pluck('id');
        }

        if ($studentIds->isEmpty()) {
            $this->command->warn('Nessuno studente trovato: DrivingSessionSeeder saltato.');
            return;
        }

        $count = 0;
        foreach ($studentIds as $studentId) {
            // Ogni studente ha sessioni su 2-4 moduli casuali
            $assignedModules = $modules->shuffle()->take(fake()->numberBetween(2, $modules->count()));

            foreach ($assignedModules as $module) {
                // 1-4 sessioni per modulo
                $numSessions = fake()->numberBetween(1, 4);

                for ($i = 0; $i < $numSessions; $i++) {
                    $conductedAt = Carbon::today()->subDays(fake()->numberBetween(1, 60));

                    DB::table('driving_sessions')->insert([
                        'student_id'        => $studentId,
                        'instructor_id'     => $instructor?->id,
                        'driving_module_id' => $module->id,
                        'conducted_at'      => $conductedAt->toDateString(),
                        'duration_minutes'  => fake()->randomElement([30, 45, 60, 90]),
                        'notes'             => fake()->randomElement(self::NOTES),
                        'recorded_by'       => $instructor?->id ?? $admin?->id,
                        'created_at'        => $conductedAt,
                        'updated_at'        => $conductedAt,
                    ]);
                    $count++;
                }
            }
        }

        $this->command->info("CREATI {$count} SESSIONI GUIDA PRATICA (Feature 9.0)");
    }
}
