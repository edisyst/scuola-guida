<?php

namespace Database\Seeders;

use App\Models\DrivingModule;
use App\Models\LicenseType;
use Illuminate\Database\Seeder;

class DrivingModuleSeeder extends Seeder
{
    public function run(): void
    {
        // Cerca la patente B — se non esiste skippa senza crashare
        $licenseTypeB = LicenseType::where('code', 'B')->first();

        if (! $licenseTypeB) {
            return;
        }

        $moduli = [
            [
                'license_type_id' => $licenseTypeB->id,
                'code'            => 'A',
                'name'            => 'Modulo A – Veicolo, manovre base e ADAS',
                'description'     => null,
                'required_hours'  => 2.0,
                'sort_order'      => 1,
            ],
            [
                'license_type_id' => $licenseTypeB->id,
                'code'            => 'B',
                'name'            => 'Modulo B – Guida urbana',
                'description'     => null,
                'required_hours'  => 3.0,
                'sort_order'      => 2,
            ],
            [
                'license_type_id' => $licenseTypeB->id,
                'code'            => 'C',
                'name'            => 'Modulo C – Autostrada ed extraurbana',
                'description'     => null,
                'required_hours'  => 2.0,
                'sort_order'      => 3,
            ],
            [
                'license_type_id' => $licenseTypeB->id,
                'code'            => 'D',
                'name'            => 'Modulo D – Guida notturna',
                'description'     => null,
                'required_hours'  => 1.0,
                'sort_order'      => 4,
            ],
        ];

        // upsert su (license_type_id, code) per idempotenza
        DrivingModule::upsert(
            $moduli,
            ['license_type_id', 'code'],
            ['name', 'description', 'required_hours', 'sort_order']
        );
    }
}
