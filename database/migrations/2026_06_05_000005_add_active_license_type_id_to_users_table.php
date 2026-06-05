<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('active_license_type_id')
                ->nullable()
                ->nullOnDelete()
                ->constrained('license_types')
                ->after('locale');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeignIdFor('LicenseType', 'active_license_type_id');
            $table->dropColumn('active_license_type_id');
        });
    }
};
