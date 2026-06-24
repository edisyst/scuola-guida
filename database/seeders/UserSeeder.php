<?php

namespace Database\Seeders;

use App\Models\LicenseType;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    // Utenti italiani per demo — password uniforme: "password"
    private const DEMO_USERS = [
        ['name' => 'Sofia Esposito',    'email' => 'sofia.esposito@demo.it',    'role' => 'viewer'],
        ['name' => 'Matteo Russo',      'email' => 'matteo.russo@demo.it',      'role' => 'viewer'],
        ['name' => 'Chiara Romano',     'email' => 'chiara.romano@demo.it',     'role' => 'viewer'],
        ['name' => 'Lorenzo Colombo',   'email' => 'lorenzo.colombo@demo.it',   'role' => 'viewer'],
        ['name' => 'Giorgia Ricci',     'email' => 'giorgia.ricci@demo.it',     'role' => 'viewer'],
        ['name' => 'Andrea Marino',     'email' => 'andrea.marino@demo.it',     'role' => 'viewer'],
        ['name' => 'Valentina Greco',   'email' => 'valentina.greco@demo.it',   'role' => 'viewer'],
        ['name' => 'Francesco Bruno',   'email' => 'f.bruno@demo.it',           'role' => 'viewer'],
        ['name' => 'Alessia Gallo',     'email' => 'alessia.gallo@demo.it',     'role' => 'viewer'],
        ['name' => 'Davide Conti',      'email' => 'davide.conti@demo.it',      'role' => 'viewer'],
        ['name' => 'Elisa Fontana',     'email' => 'elisa.fontana@demo.it',     'role' => 'viewer'],
        ['name' => 'Simone Barbieri',   'email' => 'simone.barbieri@demo.it',   'role' => 'viewer'],
        ['name' => 'Federica Morelli',  'email' => 'f.morelli@demo.it',         'role' => 'viewer'],
        ['name' => 'Riccardo Vitale',   'email' => 'r.vitale@demo.it',          'role' => 'viewer'],
        ['name' => 'Martina De Luca',   'email' => 'm.deluca@demo.it',          'role' => 'viewer'],
        ['name' => 'Emanuele Serra',    'email' => 'e.serra@demo.it',           'role' => 'editor'],
        ['name' => 'Roberta Pellegrini','email' => 'r.pellegrini@demo.it',      'role' => 'editor'],
        ['name' => 'Stefano Caruso',    'email' => 's.caruso@demo.it',          'role' => 'editor'],
        ['name' => 'Paola Montanari',   'email' => 'p.montanari@demo.it',       'role' => 'editor'],
        ['name' => 'Giacomo Ferretti',  'email' => 'g.ferretti@demo.it',        'role' => 'editor'],
    ];

    public function run(): void
    {
        foreach (self::DEMO_USERS as $data) {
            User::create([
                'name'               => $data['name'],
                'email'              => $data['email'],
                'password'           => Hash::make('password'),
                'role'               => $data['role'],
                'permissions'        => $data['role'] === 'editor' ? ['create_question', 'edit_question'] : [],
                'email_verified_at'  => now(),
                'created_at'         => now()->subDays(rand(0, 30)),
                'updated_at'         => now(),
            ]);
        }

        $this->command->info('CREATI 20 UTENTI DEMO ITALIANI (15 viewer, 5 editor)');

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

        $viewers = [];
        foreach ($registrations as $data) {
            $viewers[] = User::create([
                ...$data,
                'password' => Hash::make('password'),
                'role' => User::ROLE_VIEWER,
                'registration_status' => User::REG_PENDING,
                'registration_submitted_at' => now()->subDays(rand(1, 7)),
                'email_verified_at' => now(),
            ]);
        }

        $this->command->info("CREATI 3 ISCRIZIONI ANAGRAFICHE IN ATTESA");

        // Associa 3 viewer a patenti attive per lo studio
        $licenseB = LicenseType::where('code', 'B')->first();
        $licenseC = LicenseType::where('code', 'C')->first();
        $licenseD = LicenseType::where('code', 'D')->first();

        if ($licenseB && $viewers[0]) {
            $viewers[0]->update(['active_license_type_id' => $licenseB->id]);
        }
        if ($licenseC && $viewers[1]) {
            $viewers[1]->update(['active_license_type_id' => $licenseC->id]);
        }
        if ($licenseD && $viewers[2]) {
            $viewers[2]->update(['active_license_type_id' => $licenseD->id]);
        }

        $this->command->info("ASSOCIATI 3 VIEWER A PATENTI (B, C, D)");
    }
}
