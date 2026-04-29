<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quiz_attempts', function (Blueprint $table) {
            $table->id();

            // 🔗 relazioni
            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('quiz_id')
                ->constrained()
                ->cascadeOnDelete();

            // 📊 dati tentativo
            $table->unsignedTinyInteger('score'); // risposte corrette
            $table->unsignedTinyInteger('total_questions');

            // opzionale ma utile
            $table->unsignedInteger('duration')->nullable(); // secondi

            $table->timestamps();

            // 🔥 index performance
            $table->index(['user_id', 'quiz_id']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quiz_attempts');
    }
};
