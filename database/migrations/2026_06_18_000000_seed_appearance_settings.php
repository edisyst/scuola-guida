<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Seed the new appearance.* customization keys (Feature 13.1).
     *
     * Uses upsert with a conflict on `key` updating only metadata columns
     * (never `value`): an already-present `appearance.accent_color` keeps its
     * configured value, while the new keys are inserted with their defaults.
     */
    public function up(): void
    {
        $now = now();

        $rows = [
            [
                'key'   => 'appearance.accent_color_dark',
                'type'  => 'color',
                'group' => 'appearance',
                'label' => 'Colore accent dark mode (hex)',
                'value' => '#4aa3d4',
            ],
            [
                'key'   => 'appearance.font_family',
                'type'  => 'string',
                'group' => 'appearance',
                'label' => 'Famiglia font',
                'value' => 'system',
            ],
            [
                'key'   => 'appearance.border_radius',
                'type'  => 'string',
                'group' => 'appearance',
                'label' => 'Arrotondamento bordi',
                'value' => 'default',
            ],
            [
                'key'   => 'appearance.sidebar_skin_admin',
                'type'  => 'string',
                'group' => 'appearance',
                'label' => 'Skin sidebar — admin',
                'value' => 'sidebar-dark-danger',
            ],
            [
                'key'   => 'appearance.sidebar_skin_editor',
                'type'  => 'string',
                'group' => 'appearance',
                'label' => 'Skin sidebar — editor',
                'value' => 'sidebar-dark-primary',
            ],
            [
                'key'   => 'appearance.sidebar_skin_viewer',
                'type'  => 'string',
                'group' => 'appearance',
                'label' => 'Skin sidebar — viewer',
                'value' => 'sidebar-dark-warning',
            ],
            [
                'key'   => 'appearance.sidebar_skin_instructor',
                'type'  => 'string',
                'group' => 'appearance',
                'label' => 'Skin sidebar — instructor',
                'value' => 'sidebar-dark-success',
            ],
        ];

        $upsertRows = array_map(fn ($row) => array_merge($row, [
            'created_at' => $now,
            'updated_at' => $now,
        ]), $rows);

        DB::table('system_settings')->upsert(
            $upsertRows,
            ['key'],
            ['label', 'type', 'group', 'updated_at']
        );
    }

    public function down(): void
    {
        DB::table('system_settings')
            ->whereIn('key', [
                'appearance.accent_color_dark',
                'appearance.font_family',
                'appearance.border_radius',
                'appearance.sidebar_skin_admin',
                'appearance.sidebar_skin_editor',
                'appearance.sidebar_skin_viewer',
                'appearance.sidebar_skin_instructor',
            ])
            ->delete();
    }
};
