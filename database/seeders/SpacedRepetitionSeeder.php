<?php

namespace Database\Seeders;

use App\Models\Question;
use App\Models\QuestionReview;
use App\Models\User;
use Illuminate\Database\Seeder;

class SpacedRepetitionSeeder extends Seeder
{
    public function run(): void
    {
        $viewers     = User::where('role', User::ROLE_VIEWER)->get();
        $questionIds = Question::pluck('id')->toArray();

        if ($viewers->isEmpty() || empty($questionIds)) {
            $this->command->warn('Nessun viewer o domanda: SpacedRepetitionSeeder saltato.');
            return;
        }

        $total = 0;

        foreach ($viewers as $viewer) {
            $count  = fake()->numberBetween(20, 50);
            $picked = collect($questionIds)->shuffle()->take($count);

            foreach ($picked as $questionId) {
                $repetitions  = fake()->numberBetween(0, 8);
                $intervalDays = $repetitions === 0 ? 1 : min((int) (1.5 ** $repetitions), 90);
                $isDue        = fake()->boolean(40); // 40% già scadute

                QuestionReview::updateOrCreate(
                    ['user_id' => $viewer->id, 'question_id' => $questionId],
                    [
                        'next_review_at'   => $isDue
                            ? now()->subHours(fake()->numberBetween(1, 48))
                            : now()->addDays(fake()->numberBetween(1, 14)),
                        'interval_days'    => $intervalDays,
                        'ease_factor'      => fake()->randomFloat(2, 1.30, 2.80),
                        'repetitions'      => $repetitions,
                        'last_reviewed_at' => $repetitions > 0
                            ? now()->subDays(fake()->numberBetween(1, 30))
                            : null,
                    ]
                );
                $total++;
            }
        }

        $this->command->info("CREATI {$total} RECORD SPACED REPETITION (Feature 5.4)");
    }
}
