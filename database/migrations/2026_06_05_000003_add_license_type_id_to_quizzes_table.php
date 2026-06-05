<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quizzes', function (Blueprint $table) {
            $table->foreignId('license_type_id')
                  ->nullable()
                  ->constrained('license_types')
                  ->nullOnDelete()
                  ->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('quizzes', function (Blueprint $table) {
            $table->dropForeignIdFor(\App\Models\LicenseType::class);
            $table->dropColumn('license_type_id');
        });
    }
};
