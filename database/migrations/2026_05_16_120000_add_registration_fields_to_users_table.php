<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Dati anagrafici (obbligatori per i viewer che vogliono iscriversi agli esami)
            $table->string('first_name')->nullable()->after('name');
            $table->string('last_name')->nullable()->after('first_name');
            $table->string('address')->nullable()->after('last_name');
            $table->date('birth_date')->nullable()->after('address');
            $table->string('birth_place')->nullable()->after('birth_date');
            $table->string('fiscal_code', 32)->nullable()->after('birth_place');
            $table->string('id_document_path')->nullable()->after('fiscal_code');

            // Stato della richiesta di iscrizione definitiva
            // 'none' = non ha ancora inviato dati
            // 'pending' = ha inviato i dati, attende approvazione admin
            // 'approved' = abilitato a iscriversi agli esami ufficiali
            // 'rejected' = la richiesta è stata rifiutata dall'admin
            $table->string('registration_status', 16)
                ->default('none')
                ->index()
                ->after('id_document_path');

            $table->timestamp('registration_submitted_at')->nullable()->after('registration_status');
            $table->timestamp('registration_reviewed_at')->nullable()->after('registration_submitted_at');
            $table->foreignId('registration_reviewed_by')
                ->nullable()
                ->after('registration_reviewed_at')
                ->constrained('users')
                ->nullOnDelete();
            $table->text('registration_rejection_reason')->nullable()->after('registration_reviewed_by');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['registration_reviewed_by']);
            $table->dropColumn([
                'first_name',
                'last_name',
                'address',
                'birth_date',
                'birth_place',
                'fiscal_code',
                'id_document_path',
                'registration_status',
                'registration_submitted_at',
                'registration_reviewed_at',
                'registration_reviewed_by',
                'registration_rejection_reason',
            ]);
        });
    }
};
