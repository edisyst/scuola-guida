<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Lingua preferita dell'utente per il TESTO delle domande (Feature 7.1).
     * null = usa la lingua di default dell'applicazione (italiano).
     * Idempotente verso la Feature 7.0: se la colonna esiste già, non la ricrea.
     */
    public function up(): void
    {
        if (Schema::hasColumn('users', 'locale')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            $table->string('locale', 5)->nullable()->default(null)->after('remember_token');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('users', 'locale')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('locale');
        });
    }
};
