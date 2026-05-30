<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use ZipArchive;

class BackupCheck extends Command
{
    protected $signature = 'backup:check';

    protected $description = 'Verifica integrità e freschezza dell\'ultimo backup';

    public function handle(): int
    {
        $disk    = Storage::disk('backups');
        $appName = config('app.name');

        $files = collect($disk->allFiles($appName))
            ->filter(fn(string $f) => str_ends_with($f, '.zip'))
            ->map(fn(string $f) => [
                'path'          => $f,
                'last_modified' => $disk->lastModified($f),
            ])
            ->sortByDesc('last_modified')
            ->values();

        if ($files->isEmpty()) {
            $this->error('Nessun backup trovato sul disco "backups".');

            return self::FAILURE;
        }

        $latest      = $files->first();
        $backupAge   = now()->diffInHours(\Carbon\Carbon::createFromTimestamp($latest['last_modified']));

        if ($backupAge >= 26) {
            $this->error("L'ultimo backup ha più di 26 ore ({$backupAge}h fa).");

            return self::FAILURE;
        }

        $absolutePath = storage_path('app/backups/' . $latest['path']);

        if (!file_exists($absolutePath)) {
            $this->error("File backup non trovato: {$absolutePath}");

            return self::FAILURE;
        }

        $zip = new ZipArchive();
        $result = $zip->open($absolutePath, ZipArchive::RDONLY);

        if ($result !== true) {
            $this->error("Il file zip del backup è corrotto o illeggibile (errore: {$result}).");

            return self::FAILURE;
        }

        $numFiles = $zip->numFiles;
        $zip->close();

        $this->info("Backup OK — {$backupAge}h fa, {$numFiles} file nel pacchetto.");

        return self::SUCCESS;
    }
}
