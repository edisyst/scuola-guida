<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class FeatureSettingSeeder extends Seeder
{
    public function run(): void
    {
        $rows = [
            [
                'key'   => 'features.gamification_enabled',
                'type'  => 'boolean',
                'group' => 'features',
                'label' => 'Gamification (badge e streak)',
                'value' => '1',
            ],
            [
                'key'   => 'features.web_push_enabled',
                'type'  => 'boolean',
                'group' => 'features',
                'label' => 'Notifiche Web Push',
                'value' => '1',
            ],
            [
                'key'   => 'features.guest_homepage_enabled',
                'type'  => 'boolean',
                'group' => 'features',
                'label' => 'Homepage pubblica',
                'value' => '1',
            ],
            [
                'key'   => 'features.exam_translations_enabled',
                'type'  => 'boolean',
                'group' => 'features',
                'label' => 'Selezione lingua interfaccia',
                'value' => '1',
            ],
            [
                'key'   => 'features.driving_practice_enabled',
                'type'  => 'boolean',
                'group' => 'features',
                'label' => 'Modulo guide pratiche',
                'value' => '1',
            ],
            [
                'key'   => 'features.eu_categories_visible',
                'type'  => 'boolean',
                'group' => 'features',
                'label' => 'Categorie EU nello studio',
                'value' => '1',
            ],
            [
                'key'   => 'features.study_content_enabled',
                'type'  => 'boolean',
                'group' => 'features',
                'label' => 'Contenuti formativi StudyContent',
                'value' => '1',
            ],
        ];

        $now = now();

        DB::table('system_settings')->upsert(
            array_map(fn($row) => array_merge($row, [
                'created_at' => $now,
                'updated_at' => $now,
            ]), $rows),
            ['key'],
            ['label', 'type', 'group', 'updated_at']
        );
    }
}
