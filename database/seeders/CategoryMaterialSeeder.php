<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CategoryMaterialSeeder extends Seeder
{
    private const LINKS = [
        [
            'type'        => 'link',
            'title'       => 'Codice della Strada – testo ufficiale MIT',
            'url_or_path' => 'https://www.mit.gov.it/normativa/decreto-legislativo-30-aprile-1992-n-285',
            'content'     => null,
        ],
        [
            'type'        => 'link',
            'title'       => 'Portale Patenti – Ministero Infrastrutture',
            'url_or_path' => 'https://www.ilportaledellautomobilista.it',
            'content'     => null,
        ],
        [
            'type'        => 'link',
            'title'       => 'Segnali stradali – catalogo completo ACI',
            'url_or_path' => 'https://www.aci.it/i-servizi/normative/codice-della-strada/segnaletica-stradale.html',
            'content'     => null,
        ],
    ];

    private const NOTES = [
        [
            'type'        => 'note',
            'title'       => 'Mnemonica: precedenze agli incroci',
            'url_or_path' => null,
            'content'     => "Regola dei 3 stop:\n1. Stop al segnale → fermata obbligatoria\n2. Stop alla linea → non superarla prima di cedere\n3. Stop mentale → guardare a destra, poi sinistra, poi dritto\n\nSenza segnali: chi arriva da destra ha sempre la precedenza.",
        ],
        [
            'type'        => 'note',
            'title'       => 'Checklist pre-guida',
            'url_or_path' => null,
            'content'     => "Prima di partire verificare:\n- Specchietti regolati\n- Sedile e volante in posizione corretta\n- Cintura allacciata\n- Luci funzionanti\n- Carburante sufficiente\n- Documenti a bordo (patente, libretto, assicurazione)",
        ],
        [
            'type'        => 'note',
            'title'       => 'Limiti velocità: schema rapido',
            'url_or_path' => null,
            'content'     => "Urbano: 50 km/h\nExtraurbano secondario: 90 km/h\nExtraurbano principale: 110 km/h\nAutostrada: 130 km/h\n\nCon pioggia in autostrada: -20 km/h\nCon neve/ghiaccio: -40 km/h",
        ],
    ];

    public function run(): void
    {
        $editor     = User::where('role', User::ROLE_EDITOR)->first();
        $admin      = User::where('role', User::ROLE_ADMIN)->first();
        $authorId   = $editor?->id ?? $admin?->id;
        $categories = Category::inRandomOrder()->take(6)->get();

        if ($categories->isEmpty()) {
            $this->command->warn('Nessuna categoria trovata: CategoryMaterialSeeder saltato.');
            return;
        }

        $allMaterials = array_merge(self::LINKS, self::NOTES);
        $count        = 0;

        foreach ($categories as $index => $category) {
            // 1-3 materiali per categoria, mix link e note
            $numMaterials = fake()->numberBetween(1, 3);
            $pool         = collect($allMaterials)->shuffle();

            for ($i = 0; $i < $numMaterials; $i++) {
                $material  = $pool->get($i % count($allMaterials));
                $createdAt = now()->subDays(fake()->numberBetween(3, 45));

                DB::table('category_materials')->insert([
                    'category_id'  => $category->id,
                    'type'         => $material['type'],
                    'title'        => $material['title'],
                    'url_or_path'  => $material['url_or_path'],
                    'content'      => $material['content'],
                    'position'     => $i + 1,
                    'created_by'   => $authorId,
                    'created_at'   => $createdAt,
                    'updated_at'   => $createdAt,
                ]);
                $count++;
            }
        }

        $this->command->info("CREATI {$count} MATERIALI CATEGORIE (CategoryMaterialSeeder)");
    }
}
