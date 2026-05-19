<?php

namespace Database\Seeders;

use App\Models\Question;
use Illuminate\Database\Seeder;
use PhpOffice\PhpSpreadsheet\IOFactory;

class QuestionSeeder extends Seeder
{
    private const EXCEL_PATH = 'file_con_category_id.xlsx';
    private const CHUNK_SIZE = 500;

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
        $worksheet   = $spreadsheet->getSheetByName('Domande');

        $now     = now();
        $batch   = [];
        $total   = 0;
        $skipped = 0;

        // Riga 1 = header, si parte da riga 2
        // Colonne: A=question, B=is_true, C=image, D=category_id
        foreach ($worksheet->getRowIterator(2) as $row) {
            $cellIterator = $row->getCellIterator('A', 'D');
            $cellIterator->setIterateOnlyExistingCells(false);

            $data = [];
            foreach ($cellIterator as $cell) {
                $data[] = $cell->getValue();
            }

            [$question, $isTrue, $image, $categoryId] = array_pad($data, 4, null);

            $question = trim((string) $question);

            if ($question === '' || $categoryId === null) {
                $skipped++;
                continue;
            }

            $batch[] = [
                'category_id' => (int) $categoryId,
                'question'    => $question,
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

        $this->command->info("CREATE {$total} DOMANDE (saltate: {$skipped})");
    }
}
