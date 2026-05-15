<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class ProductionSeeder extends Seeder
{
    /**
     * Seeder di produzione.
     *
     * Esegue solo i seeder strutturali (ruoli/permessi, utente admin,
     * categorie) + le domande reali. NIENTE dati fake (quiz, tentativi,
     * utenti random) prodotti dalle factory.
     *
     * Uso:
     *   php artisan db:seed --class=Database\\Seeders\\ProductionSeeder
     */
    public function run(): void
    {
        $this->call([
            RolePermissionSeeder::class,
            AdminUserSeeder::class,
            CategorySeeder::class,
            QuestionProductionSeeder::class,
        ]);
    }
}
