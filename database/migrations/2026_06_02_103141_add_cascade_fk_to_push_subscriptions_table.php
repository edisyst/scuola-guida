<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// GDPR: push_subscriptions usa morphs per compatibilità col package.
// In questo progetto l'unico subscribable è User; aggiungiamo una FK reale
// su subscribable_id → users.id con cascade, garantendo che la cancellazione
// di un utente rimuova automaticamente le sue subscription push.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('push_subscriptions', function (Blueprint $table) {
            $table->foreign('subscribable_id')
                ->references('id')
                ->on('users')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('push_subscriptions', function (Blueprint $table) {
            $table->dropForeign(['subscribable_id']);
        });
    }
};
