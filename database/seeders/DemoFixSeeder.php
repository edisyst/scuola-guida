<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\LicenseType;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Correzioni di ordine eseguite dopo che tutti i seeder base hanno girato:
 * - assegna active_license_type_id ai viewer senza patente attiva
 * - sincronizza le categorie alle patenti (LicenseTypeSeeder gira prima di CategorySeeder)
 */
class DemoFixSeeder extends Seeder
{
    public function run(): void
    {
        $licenseB = LicenseType::where('code', 'B')->first();

        if (! $licenseB) {
            $this->command->warn('Patente B non trovata: DemoFixSeeder saltato.');
            return;
        }

        // Assegna Patente B a tutti i viewer senza active_license_type_id
        $updated = User::where('role', User::ROLE_VIEWER)
            ->whereNull('active_license_type_id')
            ->update(['active_license_type_id' => $licenseB->id]);

        $this->command->info("ASSEGNATA PATENTE B a {$updated} viewer senza patente attiva");

        // Sincronizza categorie alle patenti (LicenseTypeSeeder girava prima di CategorySeeder)
        $categoryIds = Category::pluck('id')->all();

        if (empty($categoryIds)) {
            $this->command->warn('Nessuna categoria trovata: salto associazione patenti-categorie.');
            return;
        }

        // Patente B: tutte le categorie
        $licenseB->categories()->sync($categoryIds);
        $this->command->info('Patente B: associate ' . count($categoryIds) . ' categorie');

        // Patenti A, C, D: un sottoinsieme casuale (min 5)
        foreach (['A', 'C', 'D'] as $code) {
            $license = LicenseType::where('code', $code)->first();
            if (! $license) {
                continue;
            }

            $subset = collect($categoryIds)->shuffle()->take(max(5, (int) (count($categoryIds) * 0.3)))->all();
            $license->categories()->sync($subset);
            $this->command->info("Patente {$code}: associate " . count($subset) . ' categorie');
        }
    }
}
