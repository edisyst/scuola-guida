<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('study_contents', function (Blueprint $table) {
            $table->id();
            $table->string('studyable_type');
            $table->unsignedBigInteger('studyable_id');
            $table->string('title');
            $table->longText('body');
            $table->boolean('is_published')->default(false);
            $table->unsignedSmallInteger('order')->default(0);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['studyable_type', 'studyable_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('study_contents');
    }
};
