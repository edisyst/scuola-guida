<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\CategoryTranslation;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\IOFactory;

class CategorySeeder extends Seeder
{
    private const EXCEL_IT = 'file_con_category_id.xlsx';
    private const EXCEL_EN = 'file_con_category_id_EN.xlsx';
    private const EXCEL_ES = 'file_con_category_id_ES.xlsx';

    public function run(): void
    {
        $fileIt = storage_path('app/imports/' . self::EXCEL_IT);
        $fileEn = storage_path('app/imports/' . self::EXCEL_EN);
        $fileEs = storage_path('app/imports/' . self::EXCEL_ES);

        if (!file_exists($fileIt)) {
            $this->command->error("File non trovato: {$fileIt}");
            return;
        }

        // --- IT categories ---
        $rows   = [];
        $idMap  = []; // category_id => name (for slug dedup guard)

        $reader = IOFactory::createReader('Xlsx');
        $reader->setReadDataOnly(true);
        $spreadsheet = $reader->load($fileIt);
        $worksheet   = $spreadsheet->getSheetByName('Categorie');

        // Colonne: A=category_name, B=category_id
        foreach ($worksheet->getRowIterator(2) as $row) {
            $cellIterator = $row->getCellIterator('A', 'B');
            $cellIterator->setIterateOnlyExistingCells(false);

            $data = [];
            foreach ($cellIterator as $cell) {
                $data[] = $cell->getValue();
            }

            [$name, $id] = array_pad($data, 2, null);
            $name = trim((string) $name);

            if ($name === '' || $id === null) {
                continue;
            }

            $catId = (int) $id;
            $rows[] = [
                'id'   => $catId,
                'name' => $name,
                'slug' => Str::slug($name),
            ];
            $idMap[$catId] = $name;
        }

        $spreadsheet->disconnectWorksheets();
        unset($spreadsheet);

        Category::insert($rows);
        $this->command->info('CREATE ' . count($rows) . ' CATEGORIE (IT)');

        // --- EN translations ---
        if (!file_exists($fileEn)) {
            $this->command->warn("File EN non trovato, salto traduzioni: {$fileEn}");
            return;
        }

        $translations = [];
        $now = now();

        $reader = IOFactory::createReader('Xlsx');
        $reader->setReadDataOnly(true);
        $spreadsheet = $reader->load($fileEn);
        $worksheet   = $spreadsheet->getSheetByName('Categorie');

        // Colonne: A=category_name_EN, B=category_id (stesso ID del file IT)
        foreach ($worksheet->getRowIterator(2) as $row) {
            $cellIterator = $row->getCellIterator('A', 'B');
            $cellIterator->setIterateOnlyExistingCells(false);

            $data = [];
            foreach ($cellIterator as $cell) {
                $data[] = $cell->getValue();
            }

            [$nameEn, $id] = array_pad($data, 2, null);
            $nameEn = trim((string) $nameEn);

            if ($nameEn === '' || $id === null) {
                continue;
            }

            $catId = (int) $id;

            if (!isset($idMap[$catId])) {
                continue; // categoria non trovata nel file IT, skip
            }

            $translations[] = [
                'category_id' => $catId,
                'locale'      => 'en',
                'name'        => $nameEn,
                'created_by'  => null,
                'created_at'  => $now,
                'updated_at'  => $now,
            ];
        }

        $spreadsheet->disconnectWorksheets();
        unset($spreadsheet);

        if (!empty($translations)) {
            CategoryTranslation::insert($translations);
            $this->command->info('CREATE ' . count($translations) . ' TRADUZIONI EN CATEGORIE');
        }

        // --- ES translations ---
        if (!file_exists($fileEs)) {
            $this->command->warn("File ES non trovato, salto traduzioni: {$fileEs}");
            return;
        }

        $translations = [];
        $now = now();

        $reader = IOFactory::createReader('Xlsx');
        $reader->setReadDataOnly(true);
        $spreadsheet = $reader->load($fileEs);
        $worksheet   = $spreadsheet->getSheetByName('Categorie');

        foreach ($worksheet->getRowIterator(2) as $row) {
            $cellIterator = $row->getCellIterator('A', 'B');
            $cellIterator->setIterateOnlyExistingCells(false);

            $data = [];
            foreach ($cellIterator as $cell) {
                $data[] = $cell->getValue();
            }

            [$nameEs, $id] = array_pad($data, 2, null);
            $nameEs = trim((string) $nameEs);

            if ($nameEs === '' || $id === null) {
                continue;
            }

            $catId = (int) $id;

            if (!isset($idMap[$catId])) {
                continue;
            }

            $translations[] = [
                'category_id' => $catId,
                'locale'      => 'es',
                'name'        => $nameEs,
                'created_by'  => null,
                'created_at'  => $now,
                'updated_at'  => $now,
            ];
        }

        $spreadsheet->disconnectWorksheets();
        unset($spreadsheet);

        if (!empty($translations)) {
            CategoryTranslation::insert($translations);
            $this->command->info('CREATE ' . count($translations) . ' TRADUZIONI ES CATEGORIE');
        }
    }
}
