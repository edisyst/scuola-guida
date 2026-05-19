<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('questions', function (Blueprint $table) {
            $table->string('mit_code', 20)->nullable()->unique()->after('id');
            $table->string('mit_image_code', 50)->nullable()->after('image');
        });
    }

    public function down(): void
    {
        Schema::table('questions', function (Blueprint $table) {
            $table->dropUnique(['mit_code']);
            $table->dropColumn(['mit_code', 'mit_image_code']);
        });
    }
};
