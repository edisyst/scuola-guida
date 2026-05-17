<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            [
                'id'        => 1,
                'name'      => 'Veicoli e Strade',
                'slug'      => 'veicoli-e-strade',
            ],
            [
                'id'        => 2,
                'name'      => 'Segnali di pericolo',
                'slug'      => 'segnali-di-pericolo',
            ],
            [
                'id'        => 3,
                'name'      => 'Segnali di precedenza',
                'slug'      => 'segnali-di-precedenza',
            ],
            [
                'id'        => 4,
                'name'      => 'Segnali di divieto',
                'slug'      => 'segnali-di-divieto',
            ],
            [
                'id'        => 5,
                'name'      => 'Segnali di obbligo',
                'slug'      => 'segnali-di-obbligo',
            ],
            [
                'id'        => 6,
                'name'      => 'Segnali di indicazione',
                'slug'      => 'segnali-di-indicazione',
            ],
            [
                'id'        => 7,
                'name'      => 'Segnali temporanei e complementari e pannelli integrativi',
                'slug'      => 'segnali-temporanei-e-complementari-e-pannelli-integrativi',
            ],
            [
                'id'        => 8,
                'name'      => 'Semafori, vigile e strisce',
                'slug'      => 'semafori-vigile-e-strisce',
            ],
            [
                'id'        => 9,
                'name'      => 'Luci, specchietti, autostrade e strade extraurbane principali',
                'slug'      => 'luci-specchietti-autostrade-e-strade-extraurbane-principali',
            ],
            [
                'id'        => 10,
                'name'      => 'Velocità e distanze',
                'slug'      => 'velocita-e-distanze',
            ],
            [
                'id'        => 11,
                'name'      => 'Posizione veicoli e manovre',
                'slug'      => 'posizione-veicoli-e-manovre',
            ],
            [
                'id'        => 12,
                'name'      => 'Norme sulla precedenza',
                'slug'      => 'norme-sulla-precedenza',
            ],
            [
                'id'        => 13,
                'name'      => 'Sorpasso',
                'slug'      => 'sorpasso',
            ],
            [
                'id'        => 14,
                'name'      => 'Arresto Fermata e Sosta, uso del triangolo e carico',
                'slug'      => 'arresto-fermata-e-sosta-uso-del-triangolo-e-carico',
            ],
            [
                'id'        => 15,
                'name'      => 'Norme varie',
                'slug'      => 'norme-varie',
            ],
            [
                'id'        => 16,
                'name'      => 'Incidenti, Assicurazioni e Primo soccorso',
                'slug'      => 'incidenti-assicurazioni-e-primo-soccorso',
            ],
            [
                'id'        => 17,
                'name'      => 'Veicoli e inquinamento',
                'slug'      => 'veicoli-e-inquinamento',
            ],
            [
                'id'        => 18,
                'name'      => 'Patenti',
                'slug'      => 'patenti',
            ],
        ];

        Category::insert($categories);

        $this->command->info("CREATE LE CATEGORIE");
    }
}
