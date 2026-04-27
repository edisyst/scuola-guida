<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Category;
use App\Models\Question;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(AdminUserSeeder::class);

        // User::factory(10)->create();

        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        // Crea 10 categorie
        $categories = Category::factory()->count(10)->create();

        // Crea 100 domande usando le categorie esistenti
        Question::factory()
            ->count(100)
            ->recycle($categories)
            ->create();
    }
}
