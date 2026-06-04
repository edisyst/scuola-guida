<?php

namespace Database\Seeders;

use App\Models\QuestionTranslation;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;

class QuestionSeeder extends Seeder
{
    private const EXCEL_IT   = 'file_con_category_id.xlsx';
    private const EXCEL_EN   = 'file_con_category_id_EN.xlsx';
    private const CHUNK_SIZE = 500;

    public function run(): void
    {
        $fileIt = storage_path('app/imports/' . self::EXCEL_IT);
        $fileEn = storage_path('app/imports/' . self::EXCEL_EN);

        if (!file_exists($fileIt)) {
            $this->command->error("File non trovato: {$fileIt}");
            return;
        }

        // --- IT questions ---
        // Mappa rowIndex => question_id, per collegare le traduzioni EN.
        $rowToId = $this->insertItalianQuestions($fileIt);

        $this->command->info('CREATE ' . count($rowToId) . ' DOMANDE (IT)');

        // --- EN translations ---
        if (!file_exists($fileEn)) {
            $this->command->warn("File EN non trovato, salto traduzioni: {$fileEn}");
            return;
        }

        $this->insertEnglishTranslations($fileEn, $rowToId);
    }

    /**
     * Legge il foglio "Domande" del file IT, inserisce le domande in bulk
     * e ritorna la mappa [rowIndex => question_id].
     *
     * @return array<int,int>
     */
    private function insertItalianQuestions(string $filePath): array
    {
        $reader = IOFactory::createReader('Xlsx');
        $reader->setReadDataOnly(true);
        $spreadsheet = $reader->load($filePath);
        $worksheet   = $spreadsheet->getSheetByName('Domande');

        $now        = now();
        $batch      = [];
        $rowIndexes = []; // posizione ordinata per recuperare gli ID dopo insert

        // Riga 1 = header, si parte da riga 2
        // Colonne: A=question, B=is_true, C=image, D=category_id
        foreach ($worksheet->getRowIterator(2) as $excelRow) {
            $cellIterator = $excelRow->getCellIterator('A', 'D');
            $cellIterator->setIterateOnlyExistingCells(false);

            $data = [];
            foreach ($cellIterator as $cell) {
                $data[] = $cell->getValue();
            }

            [$question, $isTrue, $image, $categoryId] = array_pad($data, 4, null);

            $question = trim((string) $question);
            if ($question === '' || $categoryId === null) {
                continue;
            }

            $rowIndex = $excelRow->getRowIndex();
            $batch[] = [
                'category_id' => (int) $categoryId,
                'question'    => $question,
                'is_true'     => (bool) $isTrue,
                'image'       => ($image !== '' && $image !== null) ? (string) $image : null,
                'created_at'  => $now,
                'updated_at'  => $now,
            ];
            $rowIndexes[] = $rowIndex;
        }

        $spreadsheet->disconnectWorksheets();
        unset($spreadsheet);

        if (empty($batch)) {
            return [];
        }

        // Insert in chunk e recupera gli ID assegnati.
        // MySQL: lastInsertId() dopo bulk insert = primo ID del batch.
        $rowToId = [];
        $offset  = 0;

        foreach (array_chunk($batch, self::CHUNK_SIZE) as $chunk) {
            DB::table('questions')->insert($chunk);

            $count   = count($chunk);
            $firstId = (int) DB::getPdo()->lastInsertId();

            foreach (range($firstId, $firstId + $count - 1) as $i => $id) {
                $rowToId[$rowIndexes[$offset + $i]] = $id;
            }

            $offset += $count;
            $this->command->line('  Inserite ' . $offset . ' domande...');
        }

        return $rowToId;
    }

    /**
     * Legge il foglio "Domande" del file EN, matcha per posizione riga,
     * inserisce le traduzioni EN in bulk.
     *
     * @param array<int,int> $rowToId  rowIndex => question_id
     */
    private function insertEnglishTranslations(string $filePath, array $rowToId): void
    {
        $reader = IOFactory::createReader('Xlsx');
        $reader->setReadDataOnly(true);
        $spreadsheet = $reader->load($filePath);
        $worksheet   = $spreadsheet->getSheetByName('Domande');

        $now        = now();
        $batch      = [];
        $total      = 0;
        $skipped    = 0;

        // Colonne: A=question_EN, B=is_true, C=image, D=category_id (stesso ordine del file IT)
        foreach ($worksheet->getRowIterator(2) as $excelRow) {
            $rowIndex = $excelRow->getRowIndex();

            if (!isset($rowToId[$rowIndex])) {
                $skipped++;
                continue;
            }

            $cellIterator = $excelRow->getCellIterator('A', 'A');
            $cellIterator->setIterateOnlyExistingCells(false);

            $questionEn = '';
            foreach ($cellIterator as $cell) {
                $questionEn = trim((string) $cell->getValue());
            }

            if ($questionEn === '') {
                $skipped++;
                continue;
            }

            $batch[] = [
                'question_id' => $rowToId[$rowIndex],
                'locale'      => 'en',
                'text'        => $questionEn,
                'created_by'  => null,
                'created_at'  => $now,
                'updated_at'  => $now,
            ];

            if (count($batch) >= self::CHUNK_SIZE) {
                QuestionTranslation::insert($batch);
                $total  += count($batch);
                $batch   = [];
                $this->command->line("  Inserite {$total} traduzioni EN...");
            }
        }

        if (!empty($batch)) {
            QuestionTranslation::insert($batch);
            $total += count($batch);
        }

        $spreadsheet->disconnectWorksheets();
        unset($spreadsheet);

        $this->command->info("CREATE {$total} TRADUZIONI EN DOMANDE (saltate: {$skipped})");
    }
}
