<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quizzes', function (Blueprint $table) {
            $table->enum('status', ['draft', 'published', 'confirmed'])
                ->default('draft')
                ->after('title');

            $table->timestamp('confirmed_at')->nullable()->after('status');

            $table->foreignId('confirmed_by')
                ->nullable()
                ->after('confirmed_at')
                ->constrained('users')
                ->nullOnDelete();
        });

        DB::table('quizzes')->where('is_active', true)->update(['status' => 'published']);
        DB::table('quizzes')->where('is_active', false)->update(['status' => 'draft']);

        Schema::table('quizzes', function (Blueprint $table) {
            $table->dropColumn('is_active');
        });
    }

    public function down(): void
    {
        Schema::table('quizzes', function (Blueprint $table) {
            $table->boolean('is_active')->default(true)->after('title');
        });

        DB::table('quizzes')->where('status', 'draft')->update(['is_active' => false]);
        DB::table('quizzes')->whereIn('status', ['published', 'confirmed'])->update(['is_active' => true]);

        Schema::table('quizzes', function (Blueprint $table) {
            $table->dropForeign(['confirmed_by']);
            $table->dropColumn(['status', 'confirmed_at', 'confirmed_by']);
        });
    }
};
