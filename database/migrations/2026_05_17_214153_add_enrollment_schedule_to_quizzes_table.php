<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quizzes', function (Blueprint $table) {
            $table->timestamp('enrollments_open_at')->nullable()->after('status');
            $table->timestamp('enrollments_close_at')->nullable()->after('enrollments_open_at');
        });
    }

    public function down(): void
    {
        Schema::table('quizzes', function (Blueprint $table) {
            $table->dropColumn(['enrollments_open_at', 'enrollments_close_at']);
        });
    }
};
