<?php

namespace App\Services;

use App\Models\Category;
use App\Models\LicenseType;
use App\Models\Question;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\ToArray;
use Maatwebsite\Excel\Facades\Excel;

class MitImportService
{
    /**
     * Importa le domande dal file Excel MIT.
     * Usato sia dal comando artisan che dal controller HTTP.
     *
     * @return object{imported:int, updated:int, skipped:int, errors:array}
     */
    public function import(
        string $filePath,
        LicenseType $licenseType,
        bool $dryRun = false,
        bool $updateExisting = false,
        ?int $topicFilter = null,
        ?callable $onProgress = null,
    ): object {
        $config = config('mit_import');
        $rows   = $this->readRows($filePath, $config);

        $counts = ['imported' => 0, 'updated' => 0, 'skipped' => 0, 'errors' => []];
        $syncedCategories = [];

        $categories   = Category::all();
        $topicMap     = $this->buildTopicMap($config['topic_map'], $categories);
        $existingCodes = Question::whereNotNull('mit_code')->pluck('id', 'mit_code');

        DB::beginTransaction();
        try {
            foreach ($rows as $index => $row) {
                // +2 per header row, +1 per indexing 0-based → human row number
                $rowNum = $index + ($config['has_header_row'] ? 2 : 1);

                try {
                    $categoryId = $this->processRow(
                        $row, $rowNum, $config,
                        $topicMap, $existingCodes,
                        $updateExisting, $topicFilter,
                        $counts,
                    );
                    if ($categoryId !== null) {
                        $syncedCategories[$categoryId] = true;
                    }
                } catch (\Throwable $e) {
                    $counts['errors'][] = "Riga {$rowNum}: " . $e->getMessage();
                    $counts['skipped']++;
                }

                if ($onProgress) {
                    ($onProgress)();
                }
            }

            // Sincronizza il pivot category_license_type per tutte le categorie dell'import
            // usando syncWithoutDetaching per preservare associazioni ad altri tipi
            if (!empty($syncedCategories)) {
                $categoryIds = array_keys($syncedCategories);
                foreach ($categoryIds as $categoryId) {
                    $licenseType->categories()->syncWithoutDetaching([$categoryId]);
                }
            }

            if ($dryRun) {
                DB::rollBack();
            } else {
                DB::commit();
            }
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }

        return (object) $counts;
    }

    private function processRow(
        array $row,
        int $rowNum,
        array $config,
        array $topicMap,
        \Illuminate\Support\Collection $existingCodes,
        bool $updateExisting,
        ?int $topicFilter,
        array &$counts,
    ): ?int {
        $mitCode      = trim((string) ($this->cell($row, $config['columns']['mit_code']) ?? ''));
        $topicCode    = (int) ($this->cell($row, $config['columns']['topic_code']) ?? 0);
        $questionText = trim((string) ($this->cell($row, $config['columns']['question']) ?? ''));
        $answerRaw    = trim((string) ($this->cell($row, $config['columns']['answer']) ?? ''));
        $imageCode    = trim((string) ($this->cell($row, $config['columns']['image_code']) ?? ''));

        if (empty($questionText)) {
            $counts['errors'][] = "Riga {$rowNum}: testo domanda vuoto, saltata.";
            $counts['skipped']++;
            return null;
        }

        if ($topicFilter !== null && $topicCode !== $topicFilter) {
            $counts['skipped']++;
            return null;
        }

        $categoryId = $topicMap[$topicCode] ?? null;
        if (!$categoryId) {
            $counts['errors'][] = "Riga {$rowNum}: argomento MIT {$topicCode} non mappato, saltata.";
            $counts['skipped']++;
            return null;
        }

        $isTrue = in_array(strtolower($answerRaw), $config['true_values'], true) ? 1 : 0;

        $data = [
            'category_id'   => $categoryId,
            'question'      => $questionText,
            'is_true'       => $isTrue,
            'mit_code'      => $mitCode ?: null,
            'mit_image_code' => $imageCode ?: null,
        ];

        // Deduplicazione per mit_code
        if ($mitCode && $existingCodes->has($mitCode)) {
            if ($updateExisting) {
                Question::where('mit_code', $mitCode)->update($data);
                $counts['updated']++;
            } else {
                $counts['skipped']++;
            }
            return $categoryId;
        }

        // Fallback: deduplicazione per testo quando mit_code è assente
        if (!$mitCode) {
            $existing = Question::where('question', $questionText)
                ->where('category_id', $categoryId)
                ->first();

            if ($existing) {
                if ($updateExisting) {
                    $existing->update($data);
                    $counts['updated']++;
                } else {
                    $counts['skipped']++;
                }
                return $categoryId;
            }
        }

        $question = Question::create($data);
        if ($mitCode) {
            $existingCodes->put($mitCode, $question->id);
        }
        $counts['imported']++;
        return $categoryId;
    }

    private function cell(array $row, int|string $colKey): mixed
    {
        return $row[$colKey] ?? null;
    }

    private function readRows(string $filePath, array $config): array
    {
        $importable = new class implements ToArray {
            public array $data = [];
            public function array(array $array): void
            {
                $this->data = $array;
            }
        };

        Excel::import($importable, $filePath);
        $rows = $importable->data;

        if ($config['has_header_row'] && !empty($rows)) {
            $headers = array_map('trim', array_shift($rows));
            $rows = array_map(function ($row) use ($headers) {
                $row = array_slice($row, 0, count($headers));
                return array_combine($headers, array_pad($row, count($headers), null));
            }, $rows);
        }

        return array_slice($rows, 0, $config['max_rows']);
    }

    private function buildTopicMap(array $topicConfig, \Illuminate\Database\Eloquent\Collection $categories): array
    {
        $map = [];
        foreach ($topicConfig as $topicCode => $categoryName) {
            $match = $categories->first(fn ($cat) =>
                str_contains(strtolower($cat->name), strtolower($categoryName))
            );
            if ($match) {
                $map[$topicCode] = $match->id;
            } else {
                Log::warning("MitImportService: nessuna categoria trovata per argomento MIT {$topicCode} ('{$categoryName}')");
            }
        }
        return $map;
    }
}
