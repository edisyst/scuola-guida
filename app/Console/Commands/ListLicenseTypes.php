<?php

namespace App\Console\Commands;

use App\Models\LicenseType;
use Illuminate\Console\Command;

class ListLicenseTypes extends Command
{
    protected $signature = 'license-types:list';

    protected $description = 'Elenca tutti i tipi di patente con statistiche';

    public function handle(): int
    {
        $licenseTypes = LicenseType::withCount('categories')
            ->orderBy('sort_order')
            ->get()
            ->map(function ($type) {
                $questionCount = $type->categories()
                    ->withCount('questions')
                    ->get()
                    ->sum('questions_count');

                return [
                    'Codice' => $type->code,
                    'Nome' => $type->name,
                    'Stato' => $type->is_active ? 'Attivo' : 'Disattivo',
                    'Categorie' => $type->categories_count,
                    'Domande' => $questionCount,
                ];
            });

        if ($licenseTypes->isEmpty()) {
            $this->warn('Nessun tipo di patente trovato.');
            return self::SUCCESS;
        }

        $this->table(
            ['Codice', 'Nome', 'Stato', 'Categorie', 'Domande'],
            $licenseTypes->toArray()
        );

        return self::SUCCESS;
    }
}
