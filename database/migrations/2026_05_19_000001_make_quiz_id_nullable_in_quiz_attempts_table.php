<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quiz_attempts', function (Blueprint $table) {
            $table->foreignId('quiz_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        // ATTENZIONE: esegui solo se non esistono record con quiz_id = null.
        Schema::table('quiz_attempts', function (Blueprint $table) {
            $table->foreignId('quiz_id')->nullable(false)->change();
        });
    }
};
