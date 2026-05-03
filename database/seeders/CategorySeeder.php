<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        // 10 categorie precise
        $categories = [
            [
                'id'        => 1,
                'name'      => 'Semafori',
                'slug'      => 'semafori',
            ],
            [
                'id'        => 2,
                'name'      => 'Obbligo',
                'slug'      => 'obbligo',
            ],
            [
                'id'        => 3,
                'name'      => 'Cartelli',
                'slug'      => 'cartelli',
            ],
            [
                'id'        => 4,
                'name'      => 'Guida',
                'slug'      => 'guida',
            ],
            [
                'id'        => 5,
                'name'      => 'Motore',
                'slug'      => 'motore',
            ],
            [
                'id'        => 6,
                'name'      => 'Incroci',
                'slug'      => 'incroci',
            ],
            [
                'id'        => 7,
                'name'      => 'Pedoni',
                'slug'      => 'pedoni',
            ],
            [
                'id'        => 8,
                'name'      => 'Pericolo',
                'slug'      => 'pericolo',
            ],
            [
                'id'        => 9,
                'name'      => 'Divieto',
                'slug'      => 'divieto',
            ],
            [
                'id'        => 10,
                'name'      => 'Motociclo',
                'slug'      => 'motociclo',
            ],
        ];

        Category::insert($categories);
    }
}
