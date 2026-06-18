<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SystemSettingSeeder extends Seeder
{
    public function run(): void
    {
        $rows = [
            [
                'key'   => 'school.name',
                'type'  => 'string',
                'group' => 'school',
                'label' => 'Nome scuola',
                'value' => config('driving.school_name', ''),
            ],
            [
                'key'   => 'school.tagline',
                'type'  => 'string',
                'group' => 'school',
                'label' => 'Slogan / tagline',
                'value' => '',
            ],
            [
                'key'   => 'school.address',
                'type'  => 'string',
                'group' => 'school',
                'label' => 'Indirizzo',
                'value' => config('driving.school_address', ''),
            ],
            [
                'key'   => 'school.phone',
                'type'  => 'string',
                'group' => 'school',
                'label' => 'Telefono',
                'value' => config('driving.school_phone', ''),
            ],
            [
                'key'   => 'school.email',
                'type'  => 'string',
                'group' => 'school',
                'label' => 'Email pubblica',
                'value' => config('driving.school_email', ''),
            ],
            [
                'key'   => 'school.license_number',
                'type'  => 'string',
                'group' => 'school',
                'label' => 'N. autorizzazione MIT',
                'value' => config('driving.school_license', ''),
            ],
            [
                'key'   => 'school.logo_path',
                'type'  => 'path',
                'group' => 'school',
                'label' => 'Logo (path)',
                'value' => '',
            ],
            [
                'key'   => 'school.logo_dark_path',
                'type'  => 'path',
                'group' => 'school',
                'label' => 'Logo dark mode (path)',
                'value' => '',
            ],
            [
                'key'   => 'appearance.accent_color',
                'type'  => 'color',
                'group' => 'appearance',
                'label' => 'Colore accent (hex)',
                'value' => '#3c8dbc',
            ],
            [
                'key'   => 'school.carousel_images',
                'type'  => 'json',
                'group' => 'school',
                'label' => 'Immagini carosello homepage (max 4)',
                'value' => '[]',
            ],
        ];

        $now = now();

        $upsertRows = array_map(fn($row) => array_merge($row, [
            'created_at' => $now,
            'updated_at' => $now,
        ]), $rows);

        DB::table('system_settings')->upsert(
            $upsertRows,
            ['key'],
            ['label', 'type', 'group', 'updated_at']
        );
    }
}
