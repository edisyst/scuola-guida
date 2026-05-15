<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::factory(20)->create()->each(function ($user) {

            $roles = ['editor', 'viewer'];

            $user->update([
                'role' => $roles[array_rand($roles)],
                'permissions' => ['create_question'],
                'created_at' => now()->subDays(rand(0, 30)), // per grafico
            ]);
        });

        $this->command->info("CREATI 20 UTENTI RANDOM");
    }
}
