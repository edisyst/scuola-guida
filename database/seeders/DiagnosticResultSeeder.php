<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\DiagnosticResult;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DiagnosticResultSeeder extends Seeder
{
    public function run(): void
    {
        $viewers    = User::where('role', User::ROLE_VIEWER)->get();
        $categories = Category::pluck('id')->all();

        if ($viewers->isEmpty() || empty($categories)) {
            $this->command->warn('Viewer o categorie mancanti: DiagnosticResultSeeder saltato.');
            return;
        }

        $total = 0;

        foreach ($viewers as $viewer) {
            // Ogni viewer ha fatto il test diagnostico in un momento diverso
            $batchId  = (string) Str::uuid();
            $takenAt  = now()->subDays(fake()->numberBetween(3, 45));

            // Risponde a un campione di categorie (5-15)
            $sampleCategories = collect($categories)->shuffle()->take(fake()->numberBetween(5, min(15, count($categories))));

            foreach ($sampleCategories as $categoryId) {
                DiagnosticResult::insert([
                    'user_id'     => $viewer->id,
                    'category_id' => $categoryId,
                    'correct'     => fake()->boolean(65), // 65% corretto, simula preparazione parziale
                    'taken_at'    => $takenAt,
                    'batch_id'    => $batchId,
                ]);
                $total++;
            }
        }

        $this->command->info("CREATI {$total} RISULTATI DIAGNOSTICI ({$viewers->count()} viewer)");
    }
}
