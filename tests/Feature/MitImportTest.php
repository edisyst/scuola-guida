<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Question;
use App\Models\User;
use App\Services\MitImportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class MitImportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(\App\Http\Middleware\EnsureTwoFactorAuthenticated::class);
    }

    private function adminUser(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    private function viewerUser(): User
    {
        return User::factory()->create(['role' => 'viewer']);
    }

    /**
     * Crea un file XLSX temporaneo con i dati forniti.
     * La prima riga è l'header, le successive sono dati.
     */
    private function createTempXlsx(array $headers, array $rows): string
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->fromArray([$headers, ...$rows]);

        $path = sys_get_temp_dir() . '/mit_test_' . uniqid() . '.xlsx';
        (new Xlsx($spreadsheet))->save($path);

        return $path;
    }

    private function defaultHeaders(): array
    {
        return ['Codice', 'Argomento', 'Domanda', 'Risposta', 'Immagine'];
    }

    /** Crea la categoria corrispondente all'argomento MIT 2 nel DB. */
    private function createSegnaliPericoloCategory(): Category
    {
        return Category::factory()->create(['name' => 'Segnali di pericolo']);
    }

    /** Crea tutte le 25 categorie MIT nel DB. */
    private function createAllMitCategories(): void
    {
        foreach (config('mit_import.topic_map') as $name) {
            Category::factory()->create(['name' => $name]);
        }
    }

    // -------------------------------------------------------------------------
    // 1. Import con file valido inserisce le nuove domande nel DB
    // -------------------------------------------------------------------------

    public function test_import_valido_inserisce_domande(): void
    {
        $this->createSegnaliPericoloCategory();

        $path = $this->createTempXlsx($this->defaultHeaders(), [
            ['B002-001', 2, 'Domanda uno', 'V', ''],
            ['B002-002', 2, 'Domanda due', 'F', ''],
        ]);

        $result = app(MitImportService::class)->import($path);

        $this->assertEquals(2, $result->imported);
        $this->assertEquals(0, $result->updated);
        $this->assertEquals(0, $result->skipped);
        $this->assertDatabaseHas('questions', ['mit_code' => 'B002-001', 'is_true' => 1]);
        $this->assertDatabaseHas('questions', ['mit_code' => 'B002-002', 'is_true' => 0]);

        unlink($path);
    }

    // -------------------------------------------------------------------------
    // 2. mit_code duplicato: default skippa, --update-existing aggiorna
    // -------------------------------------------------------------------------

    public function test_mit_code_duplicato_viene_skippato_per_default(): void
    {
        $cat = $this->createSegnaliPericoloCategory();
        Question::factory()->create(['mit_code' => 'B002-001', 'category_id' => $cat->id, 'question' => 'Vecchia']);

        $path = $this->createTempXlsx($this->defaultHeaders(), [
            ['B002-001', 2, 'Nuova domanda', 'V', ''],
        ]);

        $result = app(MitImportService::class)->import($path);

        $this->assertEquals(0, $result->imported);
        $this->assertEquals(0, $result->updated);
        $this->assertEquals(1, $result->skipped);
        $this->assertDatabaseHas('questions', ['mit_code' => 'B002-001', 'question' => 'Vecchia']);

        unlink($path);
    }

    public function test_update_existing_aggiorna_domanda_con_stesso_mit_code(): void
    {
        $cat = $this->createSegnaliPericoloCategory();
        Question::factory()->create(['mit_code' => 'B002-001', 'category_id' => $cat->id, 'question' => 'Vecchia']);

        $path = $this->createTempXlsx($this->defaultHeaders(), [
            ['B002-001', 2, 'Aggiornata', 'F', ''],
        ]);

        $result = app(MitImportService::class)->import($path, updateExisting: true);

        $this->assertEquals(0, $result->imported);
        $this->assertEquals(1, $result->updated);
        $this->assertDatabaseHas('questions', ['mit_code' => 'B002-001', 'question' => 'Aggiornata']);

        unlink($path);
    }

    // -------------------------------------------------------------------------
    // 3. Argomento MIT non mappato → riga saltata con errore
    // -------------------------------------------------------------------------

    public function test_argomento_non_mappato_viene_saltato(): void
    {
        $path = $this->createTempXlsx($this->defaultHeaders(), [
            ['X099-001', 99, 'Domanda argomento inesistente', 'V', ''],
        ]);

        $result = app(MitImportService::class)->import($path);

        $this->assertEquals(0, $result->imported);
        $this->assertEquals(1, $result->skipped);
        $this->assertNotEmpty($result->errors);

        unlink($path);
    }

    // -------------------------------------------------------------------------
    // 4. Testo domanda vuoto → riga saltata con errore
    // -------------------------------------------------------------------------

    public function test_domanda_vuota_viene_saltata(): void
    {
        $this->createSegnaliPericoloCategory();

        $path = $this->createTempXlsx($this->defaultHeaders(), [
            ['B002-001', 2, '', 'V', ''],
        ]);

        $result = app(MitImportService::class)->import($path);

        $this->assertEquals(0, $result->imported);
        $this->assertEquals(1, $result->skipped);
        $this->assertNotEmpty($result->errors);

        unlink($path);
    }

    // -------------------------------------------------------------------------
    // 5-6. Normalizzazione risposta
    // -------------------------------------------------------------------------

    /**
     * @dataProvider trueValuesProvider
     */
    public function test_valori_risposta_vera_normalizzati(string $raw): void
    {
        $cat = $this->createSegnaliPericoloCategory();

        $path = $this->createTempXlsx($this->defaultHeaders(), [
            ["B002-{$raw}", 2, "Domanda per {$raw}", $raw, ''],
        ]);

        app(MitImportService::class)->import($path);

        $this->assertDatabaseHas('questions', ['is_true' => 1, 'question' => "Domanda per {$raw}"]);

        unlink($path);
    }

    public static function trueValuesProvider(): array
    {
        return [['V'], ['VERO'], ['1'], ['TRUE'], ['v'], ['vero']];
    }

    /**
     * @dataProvider falseValuesProvider
     */
    public function test_valori_risposta_falsa_normalizzati(string $raw): void
    {
        $this->createSegnaliPericoloCategory();

        $path = $this->createTempXlsx($this->defaultHeaders(), [
            ["B002-{$raw}", 2, "Domanda per {$raw}", $raw, ''],
        ]);

        app(MitImportService::class)->import($path);

        $this->assertDatabaseHas('questions', ['is_true' => 0, 'question' => "Domanda per {$raw}"]);

        unlink($path);
    }

    public static function falseValuesProvider(): array
    {
        return [['F'], ['FALSO'], ['0'], ['FALSE']];
    }

    // -------------------------------------------------------------------------
    // 7. --dry-run non scrive nessun record nel DB
    // -------------------------------------------------------------------------

    public function test_dry_run_non_scrive_nel_db(): void
    {
        $this->createSegnaliPericoloCategory();

        $path = $this->createTempXlsx($this->defaultHeaders(), [
            ['B002-001', 2, 'Domanda dry run', 'V', ''],
        ]);

        $result = app(MitImportService::class)->import($path, dryRun: true);

        $this->assertEquals(1, $result->imported);
        $this->assertDatabaseMissing('questions', ['mit_code' => 'B002-001']);

        unlink($path);
    }

    // -------------------------------------------------------------------------
    // 8. --topic importa solo le domande dell'argomento specificato
    // -------------------------------------------------------------------------

    public function test_topic_filter_importa_solo_argomento_specificato(): void
    {
        $this->createAllMitCategories();

        $path = $this->createTempXlsx($this->defaultHeaders(), [
            ['B002-001', 2, 'Domanda argomento 2', 'V', ''],
            ['B003-001', 3, 'Domanda argomento 3', 'F', ''],
        ]);

        $result = app(MitImportService::class)->import($path, topicFilter: 2);

        $this->assertEquals(1, $result->imported);
        $this->assertDatabaseHas('questions', ['mit_code' => 'B002-001']);
        $this->assertDatabaseMissing('questions', ['mit_code' => 'B003-001']);

        unlink($path);
    }

    // -------------------------------------------------------------------------
    // 9. POST con file valido → redirect con flash success
    // -------------------------------------------------------------------------

    public function test_post_mit_import_con_file_valido_redirige_con_success(): void
    {
        Storage::fake('local');
        $this->createSegnaliPericoloCategory();

        $path = $this->createTempXlsx($this->defaultHeaders(), [
            ['B002-001', 2, 'Domanda test HTTP', 'V', ''],
        ]);

        $file = new UploadedFile($path, 'test.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);

        $response = $this->actingAs($this->adminUser())
            ->post(route('admin.questions.mit-import.store'), ['file' => $file]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        unlink($path);
    }

    // -------------------------------------------------------------------------
    // 10. POST senza file → errore validazione
    // -------------------------------------------------------------------------

    public function test_post_senza_file_restituisce_errore_validazione(): void
    {
        $response = $this->actingAs($this->adminUser())
            ->post(route('admin.questions.mit-import.store'), []);

        $response->assertSessionHasErrors('file');
    }

    // -------------------------------------------------------------------------
    // 11. POST con file troppo grande → errore validazione (fix known issue)
    // -------------------------------------------------------------------------

    public function test_post_mit_import_con_file_troppo_grande_restituisce_errore(): void
    {
        // Crea un file finto che supera il limite (simulato via UploadedFile::fake())
        $oversize = UploadedFile::fake()->create('big.xlsx', config('mit_import.max_file_size_kb') + 1);

        $response = $this->actingAs($this->adminUser())
            ->post(route('admin.questions.mit-import.store'), ['file' => $oversize]);

        $response->assertSessionHasErrors('file');
    }

    // -------------------------------------------------------------------------
    // 12. Viewer → 403
    // -------------------------------------------------------------------------

    public function test_viewer_non_puo_accedere_al_mit_import(): void
    {
        $path = $this->createTempXlsx($this->defaultHeaders(), []);
        $file = new UploadedFile($path, 'test.xlsx', null, null, true);

        $response = $this->actingAs($this->viewerUser())
            ->post(route('admin.questions.mit-import.store'), ['file' => $file]);

        $response->assertStatus(403);

        unlink($path);
    }

    // -------------------------------------------------------------------------
    // 13. Fix known issue: ImportQuestionsRequest valida max:5120
    // -------------------------------------------------------------------------

    public function test_import_generico_valida_dimensione_massima_file(): void
    {
        $oversize = UploadedFile::fake()->create('big.xlsx', 5121);

        $response = $this->actingAs($this->adminUser())
            ->post(route('admin.questions.import'), ['file' => $oversize]);

        $response->assertSessionHasErrors('file');
    }

    // -------------------------------------------------------------------------
    // 14. Invariante: imported + updated + skipped == totale righe processate
    // -------------------------------------------------------------------------

    public function test_invariante_totale_righe_coincide(): void
    {
        $this->createAllMitCategories();
        $cat = Category::where('name', 'like', '%Segnali di pericolo%')->first();
        Question::factory()->create(['mit_code' => 'B002-EX', 'category_id' => $cat->id]);

        $path = $this->createTempXlsx($this->defaultHeaders(), [
            ['B002-001', 2, 'Nuova domanda', 'V', ''],    // imported
            ['B002-EX',  2, 'Esistente',     'F', ''],    // skipped (no update)
            ['',         0, '',              'V', ''],    // skipped (vuota)
        ]);

        $result = app(MitImportService::class)->import($path);

        $total = $result->imported + $result->updated + $result->skipped;
        $this->assertEquals(3, $total, 'La somma imported+updated+skipped deve coincidere con le righe del file');

        unlink($path);
    }
}
