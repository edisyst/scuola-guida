<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('question_versions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('question_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->unsignedInteger('version_number');

            $table->text('question');
            $table->boolean('is_true');
            $table->string('image')->nullable();

            // Snapshot della categoria al momento della versione (intero non-FK:
            // la categoria potrebbe essere eliminata senza che la versione sparisca).
            $table->unsignedBigInteger('category_id')->nullable();

            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamp('created_at')->useCurrent();

            $table->unique(['question_id', 'version_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('question_versions');
    }
};
