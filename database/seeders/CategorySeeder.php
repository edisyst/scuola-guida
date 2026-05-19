<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\IOFactory;

class CategorySeeder extends Seeder
{
    private const EXCEL_PATH = 'file_con_category_id.xlsx';

    public function run(): void
    {
        $filePath = storage_path('app/imports/' . self::EXCEL_PATH);

        if (!file_exists($filePath)) {
            $this->command->error("File non trovato: {$filePath}");
            return;
        }

        $reader = IOFactory::createReader('Xlsx');
        $reader->setReadDataOnly(true);
        $spreadsheet = $reader->load($filePath);
        $worksheet   = $spreadsheet->getSheetByName('Categorie');

        $rows = [];

        // Riga 1 = header, si parte da riga 2
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

            $rows[] = [
                'id'   => (int) $id,
                'name' => $name,
                'slug' => Str::slug($name),
            ];
        }

        $spreadsheet->disconnectWorksheets();
        unset($spreadsheet);

        Category::insert($rows);

        $this->command->info('CREATE ' . count($rows) . ' CATEGORIE');
    }
}
