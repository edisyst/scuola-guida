<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('driving_modules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('license_type_id')
                  ->constrained('license_types')
                  ->cascadeOnDelete();
            $table->string('code', 5);
            $table->string('name', 100);
            $table->text('description')->nullable();
            $table->decimal('required_hours', 4, 1);
            $table->unsignedTinyInteger('sort_order')->default(0);
            $table->timestamps();

            // Codice modulo univoco per tipo di patente
            $table->unique(['license_type_id', 'code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('driving_modules');
    }
};
