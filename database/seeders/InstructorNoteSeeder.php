<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class InstructorNoteSeeder extends Seeder
{
    private const NOTES = [
        'Buona comprensione della segnaletica stradale. Deve migliorare la gestione delle rotatorie.',
        'Progressi evidenti nelle manovre di parcheggio. Ancora qualche incertezza in retromarcia.',
        'Ottima attenzione agli specchietti. Tempi di reazione nella guida urbana da affinare.',
        'Ha superato brillantemente il percorso in autostrada. Prossima sessione: guida notturna.',
        'Difficoltà con le precedenze agli incroci non segnalati. Ripasso teorico consigliato.',
        'Guida fluida e sicura. Pronto per la simulazione d\'esame.',
        'Buon controllo del veicolo. Deve lavorare sulla distanza di sicurezza in coda.',
        'Prima sessione completata. Nervosismo iniziale, poi buon adattamento al traffico urbano.',
        'Manovre base acquisite. Passaggio alla guida extraurbana nella prossima lezione.',
        'Eccellente padronanza del cambio. Attenzione ai pedoni in zona residenziale.',
        'Sessione di guida notturna superata. Uso corretto degli anabbaglianti.',
        'Ripasso parallelo: bene. Inversione a U su strada stretta: da praticare ancora.',
    ];

    public function run(): void
    {
        $instructor = User::where('role', User::ROLE_INSTRUCTOR)->first();

        if (! $instructor) {
            $this->command->warn('Nessun instructor trovato: InstructorNoteSeeder saltato.');
            return;
        }

        $studentIds = DB::table('instructor_student')
            ->where('instructor_id', $instructor->id)
            ->pluck('student_id');

        if ($studentIds->isEmpty()) {
            $this->command->warn('Nessuno studente assegnato: InstructorNoteSeeder saltato. Eseguire InstructorStudentSeeder prima.');
            return;
        }

        $count = 0;
        foreach ($studentIds as $studentId) {
            $numNotes = fake()->numberBetween(2, 5);
            $notes    = collect(self::NOTES)->shuffle()->take($numNotes);

            foreach ($notes as $body) {
                $createdAt = now()->subDays(fake()->numberBetween(1, 45));

                DB::table('instructor_notes')->insert([
                    'instructor_id' => $instructor->id,
                    'student_id'    => $studentId,
                    'body'          => $body,
                    'created_by'    => $instructor->id,
                    'created_at'    => $createdAt,
                    'updated_at'    => $createdAt,
                ]);
                $count++;
            }
        }

        $this->command->info("CREATI {$count} NOTE ISTRUTTORE (Feature 6.8)");
    }
}
