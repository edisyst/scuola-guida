<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('diagnostic_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();
            $table->boolean('correct');
            $table->timestamp('taken_at');
            $table->string('batch_id', 36)->index();

            $table->index(['user_id', 'category_id', 'taken_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('diagnostic_results');
    }
};
