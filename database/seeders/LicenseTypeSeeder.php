<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\LicenseType;
use Illuminate\Database\Seeder;

class LicenseTypeSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            ['code' => 'AM', 'name' => 'Patente AM', 'sort_order' => 0],
            ['code' => 'A1', 'name' => 'Patente A1', 'sort_order' => 1],
            ['code' => 'A2', 'name' => 'Patente A2', 'sort_order' => 2],
            ['code' => 'A', 'name' => 'Patente A', 'sort_order' => 3],
            ['code' => 'B', 'name' => 'Patente B', 'exam_questions' => 30, 'exam_minutes' => 20, 'exam_max_errors' => 3, 'sort_order' => 4],
            ['code' => 'B96', 'name' => 'Patente B96', 'sort_order' => 5],
            ['code' => 'BE', 'name' => 'Patente BE', 'sort_order' => 6],
            ['code' => 'C1', 'name' => 'Patente C1', 'sort_order' => 7],
            ['code' => 'C1E', 'name' => 'Patente C1E', 'sort_order' => 8],
            ['code' => 'C', 'name' => 'Patente C', 'sort_order' => 9],
            ['code' => 'CE', 'name' => 'Patente CE', 'sort_order' => 10],
            ['code' => 'D1', 'name' => 'Patente D1', 'sort_order' => 11],
            ['code' => 'D1E', 'name' => 'Patente D1E', 'sort_order' => 12],
            ['code' => 'D', 'name' => 'Patente D', 'sort_order' => 13],
            ['code' => 'DE', 'name' => 'Patente DE', 'sort_order' => 14],
            ['code' => 'CQC_M', 'name' => 'CQC Merci', 'sort_order' => 15],
            ['code' => 'CQC_P', 'name' => 'CQC Persone', 'sort_order' => 16],
        ];

        foreach ($types as $type) {
            LicenseType::upsert(
                [array_merge($type, ['is_active' => true])],
                ['code'],
                array_keys($type)
            );
        }

        $this->command->info('Seeded ' . count($types) . ' license types');

        // Associa categorie alle patenti
        $this->attachCategories();
    }

    private function attachCategories(): void
    {
        $categories = Category::pluck('id')->all();

        if (empty($categories)) {
            $this->command->warn('Nessuna categoria trovata, salto associazioni');
            return;
        }

        // Patente B: tutte le categorie
        $licenseB = LicenseType::where('code', 'B')->first();
        if ($licenseB) {
            $licenseB->categories()->sync($categories);
            $this->command->info("Patente B: associato " . count($categories) . " categorie");
        }

        // Patenti A, C, D: 2 categorie random
        foreach (['A', 'C', 'D'] as $code) {
            $license = LicenseType::where('code', $code)->first();
            if ($license) {
                $randomCategories = array_rand($categories, min(2, count($categories)));
                $randomCategories = is_array($randomCategories)
                    ? array_map(fn($idx) => $categories[$idx], $randomCategories)
                    : [$categories[$randomCategories]];

                $license->categories()->sync($randomCategories);
                $this->command->info("Patente {$code}: associato " . count($randomCategories) . " categorie");
            }
        }
    }
}
