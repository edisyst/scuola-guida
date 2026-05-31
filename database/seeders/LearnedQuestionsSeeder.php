<?php

namespace Database\Seeders;

use App\Models\LearnedQuestion;
use App\Models\Question;
use App\Models\User;
use Illuminate\Database\Seeder;

class LearnedQuestionsSeeder extends Seeder
{
    public function run(): void
    {
        $viewers     = User::where('role', User::ROLE_VIEWER)->get();
        $questionIds = Question::pluck('id')->toArray();

        if ($viewers->isEmpty() || empty($questionIds)) {
            $this->command->warn('Nessun viewer o domanda: LearnedQuestionsSeeder saltato.');
            return;
        }

        $total = 0;

        foreach ($viewers as $viewer) {
            // ogni viewer segna come imparate il 10-30% delle domande disponibili
            $count  = max(1, (int) (count($questionIds) * fake()->randomFloat(2, 0.10, 0.30)));
            $picked = collect($questionIds)->shuffle()->take($count);

            foreach ($picked as $questionId) {
                LearnedQuestion::insertOrIgnore([
                    'user_id'     => $viewer->id,
                    'question_id' => $questionId,
                    'marked_at'   => now()->subDays(fake()->numberBetween(1, 60)),
                ]);
                $total++;
            }
        }

        $this->command->info("CREATI {$total} DOMANDE APPRESE (Feature 5.1)");
    }
}
