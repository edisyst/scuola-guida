<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Replace English placeholder if still present; leave custom values untouched
        DB::table('system_settings')
            ->where('key', 'school.tagline')
            ->where('value', 'LIKE', '%Edoardo is building ScuolaGUIDA%')
            ->update(['value' => '']);
    }

    public function down(): void
    {
        // Cannot restore the original placeholder; intentional no-op
    }
};
