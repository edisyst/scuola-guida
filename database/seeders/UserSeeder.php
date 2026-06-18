<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

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

        // 3 iscrizioni anagrafiche in attesa
        $registrations = [
            [
                'name' => 'Marco Rossi',
                'email' => 'marco.rossi@test.it',
                'first_name' => 'Marco',
                'last_name' => 'Rossi',
                'birth_date' => '1990-03-15',
                'birth_place' => 'Milano',
                'address' => 'Via Roma 10, 20121 Milano',
                'fiscal_code' => 'RSSMRC90C15F205T',
                'id_document_path' => null,
            ],
            [
                'name' => 'Giulia Verdi',
                'email' => 'giulia.verdi@test.it',
                'first_name' => 'Giulia',
                'last_name' => 'Verdi',
                'birth_date' => '1995-07-22',
                'birth_place' => 'Roma',
                'address' => 'Via del Corso 50, 00186 Roma',
                'fiscal_code' => 'VRDGLI95L62H501K',
                'id_document_path' => null,
            ],
            [
                'name' => 'Luca Bianchi',
                'email' => 'luca.bianchi@test.it',
                'first_name' => 'Luca',
                'last_name' => 'Bianchi',
                'birth_date' => '1988-11-08',
                'birth_place' => 'Torino',
                'address' => 'Via Po 5, 10124 Torino',
                'fiscal_code' => 'BNCHLC88S08L219E',
                'id_document_path' => null,
            ],
        ];

        foreach ($registrations as $data) {
            User::create([
                ...$data,
                'password' => Hash::make('password'),
                'role' => User::ROLE_VIEWER,
                'registration_status' => User::REG_PENDING,
                'registration_submitted_at' => now()->subDays(rand(1, 7)),
                'email_verified_at' => now(),
            ]);
        }

        $this->command->info("CREATI 3 ISCRIZIONI ANAGRAFICHE IN ATTESA");
    }
}
