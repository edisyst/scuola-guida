<?php

namespace App\Console\Commands;

use App\Models\LicenseType;
use App\Services\MitImportService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ImportMitQuestions extends Command
{
    protected $signature = 'questions:import-mit
        {file : Path assoluto o relativo al file Excel MIT}
        {--dry-run : Analizza il file senza scrivere nel DB}
        {--update-existing : Aggiorna le domande esistenti (default: skippa i duplicati)}
        {--license-type=B : Codice tipo di patente (default: B per retrocompatibilità)}
        {--topic= : Importa solo le domande dell\'argomento MIT specificato (1-25)}';

    protected $description = 'Importa domande dal listato ufficiale MIT';

    public function handle(MitImportService $service): int
    {
        $filePath = $this->argument('file');
        $licenseTypeCode = $this->option('license-type');

        if (!file_exists($filePath)) {
            $this->error("File non trovato: {$filePath}");
            return self::FAILURE;
        }

        $licenseType = LicenseType::where('code', $licenseTypeCode)->first();
        if (!$licenseType) {
            $this->error("Tipo di patente non trovato: {$licenseTypeCode}");
            return self::FAILURE;
        }

        $this->info("Tipo di patente: {$licenseType->name} ({$licenseType->code})");
        $this->newLine();

        $this->info('Configurazione colonne:');
        $this->table(
            ['Campo', 'Colonna Excel'],
            collect(config('mit_import.columns'))
                ->map(fn ($col, $field) => [$field, $col])
                ->values()
                ->toArray()
        );

        if ($this->option('dry-run')) {
            $this->warn('[DRY RUN] Nessuna modifica verrà applicata al database.');
        }

        if (!$this->confirm("Procedere con l'import da '{$filePath}'?", true)) {
            $this->info('Import annullato.');
            return self::SUCCESS;
        }

        $bar = $this->output->createProgressBar();
        $bar->start();

        $start  = microtime(true);
        $result = $service->import(
            filePath:       $filePath,
            licenseType:    $licenseType,
            dryRun:         (bool) $this->option('dry-run'),
            updateExisting: (bool) $this->option('update-existing'),
            topicFilter:    $this->option('topic') !== null ? (int) $this->option('topic') : null,
            onProgress:     fn () => $bar->advance(),
        );

        $bar->finish();
        $this->newLine(2);

        $elapsed = round(microtime(true) - $start, 1);

        $this->table(
            ['Stato', 'Conteggio'],
            [
                ['Importate (nuove)', $result->imported],
                ['Aggiornate',        $result->updated],
                ['Saltate',           $result->skipped],
                ['Errori',            count($result->errors)],
            ]
        );

        if (!empty($result->errors)) {
            $this->warn('Righe con problemi:');
            foreach ($result->errors as $error) {
                $this->line("  {$error}");
            }
        }

        $this->info("Import completato in {$elapsed}s");

        Log::info('MitImport completato', [
            'file'           => $filePath,
            'license_type'   => $licenseType->code,
            'imported'       => $result->imported,
            'updated'        => $result->updated,
            'skipped'        => $result->skipped,
            'errors'         => count($result->errors),
            'dry_run'        => $this->option('dry-run'),
        ]);

        return empty($result->errors) ? self::SUCCESS : self::FAILURE;
    }
}
