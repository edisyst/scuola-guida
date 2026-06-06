<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class CleanupDrivingAttestations extends Command
{
    protected $signature = 'driving:cleanup-attestations';

    protected $description = 'Remove driving attestation PDFs older than 24 hours';

    public function handle()
    {
        $disk = Storage::disk('local');
        $directory = 'private/driving-attestations';

        if (!$disk->exists($directory)) {
            $this->info('No attestations directory found.');
            return 0;
        }

        $files = $disk->files($directory);
        $now = now()->timestamp;
        $cutoff = 24 * 60 * 60; // 24 hours in seconds
        $deleted = 0;

        foreach ($files as $file) {
            $lastModified = $disk->lastModified($file);
            if ($now - $lastModified > $cutoff) {
                $disk->delete($file);
                $deleted++;
            }
        }

        $this->info("Deleted $deleted old attestation files.");

        return 0;
    }
}
