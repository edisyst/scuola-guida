<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Rimuove le chiavi appearance.* da system_settings (Feature 15.0).
     *
     * Dal lotto 15.0 i colori, il font e il border-radius sono costanti del
     * design system (public/css/scuola-guida.css) e non sono più configurabili
     * dall'admin. Le skin sidebar saranno ridisegnate nel lotto 15.1.
     * Migration idempotente: usa whereIn + delete, sicura se le chiavi
     * sono già assenti.
     */
    public function up(): void
    {
        DB::table('system_settings')
            ->whereIn('key', [
                'appearance.accent_color',
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

    /**
     * Ripristina le chiavi appearance.* con i valori predefiniti.
     * Usa upsert per idempotenza (sicuro se le chiavi esistono già).
     */
    public function down(): void
    {
        $now = now();

        $rows = [
            ['key' => 'appearance.accent_color',           'type' => 'color',  'group' => 'appearance', 'label' => 'Colore accent (hex)',           'value' => '#3c8dbc'],
            ['key' => 'appearance.accent_color_dark',      'type' => 'color',  'group' => 'appearance', 'label' => 'Colore accent dark mode (hex)', 'value' => '#4aa3d4'],
            ['key' => 'appearance.font_family',            'type' => 'string', 'group' => 'appearance', 'label' => 'Famiglia font',                 'value' => 'system'],
            ['key' => 'appearance.border_radius',          'type' => 'string', 'group' => 'appearance', 'label' => 'Arrotondamento bordi',          'value' => 'default'],
            ['key' => 'appearance.sidebar_skin_admin',     'type' => 'string', 'group' => 'appearance', 'label' => 'Skin sidebar — admin',          'value' => 'sidebar-dark-danger'],
            ['key' => 'appearance.sidebar_skin_editor',    'type' => 'string', 'group' => 'appearance', 'label' => 'Skin sidebar — editor',         'value' => 'sidebar-dark-primary'],
            ['key' => 'appearance.sidebar_skin_viewer',    'type' => 'string', 'group' => 'appearance', 'label' => 'Skin sidebar — viewer',         'value' => 'sidebar-dark-warning'],
            ['key' => 'appearance.sidebar_skin_instructor','type' => 'string', 'group' => 'appearance', 'label' => 'Skin sidebar — instructor',     'value' => 'sidebar-dark-success'],
        ];

        DB::table('system_settings')->upsert(
            array_map(fn ($row) => array_merge($row, ['created_at' => $now, 'updated_at' => $now]), $rows),
            ['key'],
            ['label', 'type', 'group', 'value', 'updated_at']
        );
    }
};
