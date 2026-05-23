<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('category_materials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();
            $table->enum('type', ['pdf', 'link', 'note']);
            $table->string('title', 255);
            $table->string('url_or_path', 1000)->nullable();
            $table->text('content')->nullable();
            $table->integer('position')->default(0);
            $table->foreignId('created_by')->nullable()->references('id')->on('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['category_id', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('category_materials');
    }
};
