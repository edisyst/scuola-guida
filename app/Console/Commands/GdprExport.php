<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\GdprExportService;
use Illuminate\Console\Command;

class GdprExport extends Command
{
    protected $signature = 'gdpr:export
        {user? : ID o email dell\'utente (ometti con --cleanup-only)}
        {--cleanup-only : Esegui solo il cleanup dei file vecchi, senza generare un export}';

    protected $description = 'Esporta i dati personali di un utente in formato ZIP (GDPR art. 20). Pulisce automaticamente i file più vecchi di 24h.';

    public function handle(GdprExportService $service): int
    {
        $service->cleanupOldExports();
        $this->line('Cleanup file vecchi eseguito.');

        if ($this->option('cleanup-only')) {
            return self::SUCCESS;
        }

        $identifier = $this->argument('user');

        if (! $identifier) {
            $this->error('Specifica l\'utente (ID o email) oppure usa --cleanup-only.');
            return self::FAILURE;
        }

        $user = is_numeric($identifier)
            ? User::find((int) $identifier)
            : User::where('email', $identifier)->first();

        if (! $user) {
            $this->error("Utente '{$identifier}' non trovato.");
            return self::FAILURE;
        }

        $this->info("Generazione export per: {$user->email} (ID {$user->id})...");

        $zipPath = $service->generateZip($user);

        $this->info("Export generato: {$zipPath}");

        return self::SUCCESS;
    }
}
