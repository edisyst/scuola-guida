<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('driving_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')
                  ->constrained('users')
                  ->cascadeOnDelete();
            $table->foreignId('instructor_id')
                  ->nullable()
                  ->constrained('users')
                  ->nullOnDelete();
            $table->foreignId('driving_module_id')
                  ->constrained('driving_modules')
                  ->restrictOnDelete();
            $table->date('conducted_at');
            $table->unsignedSmallInteger('duration_minutes');
            $table->text('notes')->nullable();
            $table->foreignId('recorded_by')
                  ->nullable()
                  ->constrained('users')
                  ->nullOnDelete();
            $table->timestamps();

            // Indice per query di avanzamento studente per modulo
            $table->index(['student_id', 'driving_module_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('driving_sessions');
    }
};
