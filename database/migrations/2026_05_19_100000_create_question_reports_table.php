<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('question_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('question_id')
                  ->constrained()
                  ->cascadeOnDelete();
            $table->foreignId('user_id')
                  ->constrained()
                  ->cascadeOnDelete();
            $table->text('body');
            $table->enum('type', [
                'risposta_errata',
                'testo_ambiguo',
                'immagine_mancante',
                'contenuto_obsoleto',
                'altro',
            ])->default('altro');
            $table->enum('status', ['pending', 'accepted', 'rejected'])
                  ->default('pending');
            $table->text('admin_note')->nullable();
            $table->foreignId('resolved_by')->nullable()
                  ->constrained('users')
                  ->nullOnDelete();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
            $table->index(['question_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('question_reports');
    }
};
