<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quiz_attempts', function (Blueprint $table) {
            $table->foreignId('quiz_enrollment_id')
                ->nullable()
                ->after('quiz_id')
                ->constrained('quiz_enrollments')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('quiz_attempts', function (Blueprint $table) {
            $table->dropForeign(['quiz_enrollment_id']);
            $table->dropColumn('quiz_enrollment_id');
        });
    }
};
