<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Question;
use Illuminate\Database\Seeder;
use PhpOffice\PhpSpreadsheet\IOFactory;

class QuestionProductionSeeder extends Seeder
{
    private const EXCEL_PATH  = 'domande-e-categorie.xlsx';
    private const CHUNK_SIZE  = 500;

    public function run(): void
    {
        $filePath = base_path(self::EXCEL_PATH);

        if (!file_exists($filePath)) {
            $this->command->error("File non trovato: {$filePath}");
            return;
        }

        // Mappa nome categoria => id (dipende da CategorySeeder già eseguito)
        $categoryMap = Category::pluck('id', 'name')->all();

        if (empty($categoryMap)) {
            $this->command->error('Nessuna categoria trovata. Eseguire prima CategorySeeder.');
            return;
        }

        $reader = IOFactory::createReader('Xlsx');
        $reader->setReadDataOnly(true);
        $spreadsheet = $reader->load($filePath);
        $worksheet   = $spreadsheet->getSheet(0);

        $now   = now();
        $batch = [];
        $total = 0;
        $skipped = 0;

        // Riga 1 = header, si parte da riga 2
        foreach ($worksheet->getRowIterator(2) as $row) {
            $cellIterator = $row->getCellIterator('A', 'D');
            $cellIterator->setIterateOnlyExistingCells(false);

            $data = [];
            foreach ($cellIterator as $cell) {
                $data[] = $cell->getValue();
            }

            [$questionName, $isTrue, $image, $categoryName] = array_pad($data, 4, null);

            $questionName = trim((string) $questionName);
            $categoryName = trim((string) $categoryName);

            if ($questionName === '' || !isset($categoryMap[$categoryName])) {
                $skipped++;
                continue;
            }

            $batch[] = [
                'category_id' => $categoryMap[$categoryName],
                'question'    => $questionName,
                'is_true'     => (bool) $isTrue,
                'image'       => ($image !== '' && $image !== null) ? (string) $image : null,
                'created_at'  => $now,
                'updated_at'  => $now,
            ];

            if (count($batch) >= self::CHUNK_SIZE) {
                Question::insert($batch);
                $total += count($batch);
                $batch  = [];
                $this->command->line("  Inserite {$total} domande...");
            }
        }

        if (!empty($batch)) {
            Question::insert($batch);
            $total += count($batch);
        }

        $spreadsheet->disconnectWorksheets();
        unset($spreadsheet);

        $this->command->info("CREATE {$total} DOMANDE DI PRODUZIONE (saltate: {$skipped})");
    }
}
