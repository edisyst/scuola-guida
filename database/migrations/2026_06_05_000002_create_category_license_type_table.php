<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('category_license_type', function (Blueprint $table) {
            $table->foreignId('category_id')->constrained('categories')->cascadeOnDelete();
            $table->foreignId('license_type_id')->constrained('license_types')->cascadeOnDelete();
            $table->primary(['category_id', 'license_type_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('category_license_type');
    }
};
