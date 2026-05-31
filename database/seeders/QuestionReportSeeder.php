<?php

namespace Database\Seeders;

use App\Models\Question;
use App\Models\QuestionReport;
use App\Models\User;
use Illuminate\Database\Seeder;

class QuestionReportSeeder extends Seeder
{
    public function run(): void
    {
        $viewers   = User::where('role', User::ROLE_VIEWER)->get();
        $questions = Question::inRandomOrder()->limit(40)->get();
        $admin     = User::where('role', User::ROLE_ADMIN)->first();

        if ($viewers->isEmpty() || $questions->isEmpty()) {
            $this->command->warn('Nessun viewer o domanda: QuestionReportSeeder saltato.');
            return;
        }

        $total = 0;

        foreach ($questions as $question) {
            // 0-3 segnalazioni per domanda, con bias verso 0-1
            $numReports = fake()->randomElement([0, 0, 1, 1, 1, 2, 2, 3]);
            $reporters  = $viewers->shuffle()->take($numReports);

            foreach ($reporters as $viewer) {
                // doppio peso su pending per avere metriche visibili nella dashboard
                $status = fake()->randomElement([
                    QuestionReport::STATUS_PENDING,
                    QuestionReport::STATUS_PENDING,
                    QuestionReport::STATUS_ACCEPTED,
                    QuestionReport::STATUS_REJECTED,
                ]);

                $isResolved = $status !== QuestionReport::STATUS_PENDING;
                $resolvedAt = $isResolved ? now()->subDays(fake()->numberBetween(1, 15)) : null;

                QuestionReport::insertOrIgnore([
                    'question_id' => $question->id,
                    'user_id'     => $viewer->id,
                    'body'        => fake()->sentence(fake()->numberBetween(5, 15)),
                    'type'        => fake()->randomElement(array_keys(QuestionReport::types())),
                    'status'      => $status,
                    'admin_note'  => $isResolved ? fake()->optional(0.5)->sentence(5) : null,
                    'resolved_by' => $isResolved ? $admin?->id : null,
                    'resolved_at' => $resolvedAt,
                    'created_at'  => now()->subDays(fake()->numberBetween(1, 30)),
                    'updated_at'  => now()->subDays(fake()->numberBetween(0, 10)),
                ]);
                $total++;
            }
        }

        $this->command->info("CREATI {$total} SEGNALAZIONI DOMANDE (Feature 6.5)");
    }
}
