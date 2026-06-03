<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\User;
use App\Services\GdprExportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class GdprExportTest extends TestCase
{
    use RefreshDatabase;

    private function makeViewer(): User
    {
        return User::factory()->create(['role' => User::ROLE_VIEWER]);
    }

    private function makeAdmin(): User
    {
        return User::factory()->create(['role' => User::ROLE_ADMIN]);
    }

    // ─────────────────────────────────────────────
    // buildExport()
    // ─────────────────────────────────────────────

    public function test_build_export_returns_all_expected_keys(): void
    {
        Storage::fake('local');
        $viewer = $this->makeViewer();

        $service = app(GdprExportService::class);
        $result  = $service->buildExport($viewer);

        $this->assertArrayHasKey('meta', $result);
        $this->assertArrayHasKey('anagrafica', $result);
        $this->assertArrayHasKey('quiz_attempts', $result);
        $this->assertArrayHasKey('saved_questions', $result);
        $this->assertArrayHasKey('learned_questions', $result);
        $this->assertArrayHasKey('question_flags', $result);
        $this->assertArrayHasKey('diagnostic', $result);
        $this->assertArrayHasKey('spaced_repetition', $result);
        $this->assertArrayHasKey('activity', $result);
        $this->assertArrayHasKey('badges', $result);
    }

    public function test_build_export_meta_contains_email(): void
    {
        $viewer  = $this->makeViewer();
        $service = app(GdprExportService::class);
        $result  = $service->buildExport($viewer);

        $this->assertSame($viewer->email, $result['meta']['email']);
    }

    // ─────────────────────────────────────────────
    // Anonymized user: no exceptions
    // ─────────────────────────────────────────────

    public function test_anonymized_user_does_not_throw_during_export(): void
    {
        Storage::fake('local');
        $viewer = User::factory()->create([
            'role'       => User::ROLE_VIEWER,
            'name'       => 'Utente Anonimo 99',
            'email'      => 'anonimo-99@eliminato.invalid',
            'first_name' => null,
            'last_name'  => null,
            'address'    => null,
            'birth_date' => null,
            'birth_place'=> null,
            'fiscal_code'=> null,
        ]);

        $service = app(GdprExportService::class);

        $result = $service->buildExport($viewer);

        $this->assertSame('[anonimizzato]', $result['anagrafica']['first_name']);
        $this->assertSame('[anonimizzato]', $result['anagrafica']['last_name']);
    }

    // ─────────────────────────────────────────────
    // HTTP: viewer scarica i propri dati
    // ─────────────────────────────────────────────

    public function test_viewer_can_download_own_data(): void
    {
        Storage::fake('local');
        Storage::fake('public');

        $viewer = $this->makeViewer();

        $response = $this->actingAs($viewer)->get(route('profile.download-data'));

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/zip');
    }

    // ─────────────────────────────────────────────
    // HTTP: viewer NON può scaricare dati altrui
    // ─────────────────────────────────────────────

    public function test_viewer_cannot_download_another_users_data(): void
    {
        $viewer  = $this->makeViewer();
        $other   = $this->makeViewer();

        $response = $this->actingAs($viewer)->get(route('admin.users.download-data', $other));

        $response->assertStatus(403);
    }

    // ─────────────────────────────────────────────
    // HTTP: admin può scaricare i dati di qualsiasi utente
    // ─────────────────────────────────────────────

    public function test_admin_can_download_any_users_data(): void
    {
        Storage::fake('local');
        Storage::fake('public');

        $admin  = $this->makeAdmin();
        $viewer = $this->makeViewer();

        $response = $this->actingAs($admin)->get(route('admin.users.download-data', $viewer));

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/zip');
    }

    // ─────────────────────────────────────────────
    // Audit log viene creato ad ogni export
    // ─────────────────────────────────────────────

    public function test_audit_log_is_created_on_profile_download(): void
    {
        Storage::fake('local');
        Storage::fake('public');

        $viewer = $this->makeViewer();

        $this->actingAs($viewer)->get(route('profile.download-data'));

        $this->assertDatabaseHas('audit_logs', [
            'user_id'    => $viewer->id,
            'event'      => 'gdpr_export',
            'model_type' => User::class,
            'model_id'   => $viewer->id,
        ]);
    }

    public function test_audit_log_is_created_on_admin_export(): void
    {
        Storage::fake('local');
        Storage::fake('public');

        $admin  = $this->makeAdmin();
        $viewer = $this->makeViewer();

        $this->actingAs($admin)->get(route('admin.users.download-data', $viewer));

        $this->assertDatabaseHas('audit_logs', [
            'user_id'    => $admin->id,
            'event'      => 'gdpr_export',
            'model_type' => User::class,
            'model_id'   => $viewer->id,
        ]);
    }

    // ─────────────────────────────────────────────
    // cleanupOldExports() rimuove file > 24h
    // ─────────────────────────────────────────────

    public function test_cleanup_removes_files_older_than_24_hours(): void
    {
        Storage::fake('local');

        $directory = 'private/gdpr-exports';
        Storage::disk('local')->makeDirectory($directory);

        // File vecchio: creato 25h fa — tocca il timestamp modificando il file reale
        $oldFilename = 'gdpr_export_1_old.zip';
        $oldPath     = Storage::disk('local')->path("{$directory}/{$oldFilename}");
        Storage::disk('local')->put("{$directory}/{$oldFilename}", 'old data');
        touch($oldPath, now()->subHours(25)->timestamp);

        // File recente
        Storage::disk('local')->put("{$directory}/gdpr_export_2_new.zip", 'new data');

        $service = app(GdprExportService::class);
        $service->cleanupOldExports();

        $this->assertFalse(Storage::disk('local')->exists("{$directory}/{$oldFilename}"));
        $this->assertTrue(Storage::disk('local')->exists("{$directory}/gdpr_export_2_new.zip"));
    }

    // ─────────────────────────────────────────────
    // Guest viene rediretto al login
    // ─────────────────────────────────────────────

    public function test_guest_is_redirected_from_profile_download(): void
    {
        $response = $this->get(route('profile.download-data'));

        $response->assertRedirect(route('login'));
    }
}
