<?php

namespace Tests\Feature;

use App\Models\DrivingModule;
use App\Models\DrivingSession;
use App\Models\LicenseType;
use App\Models\User;
use App\Services\DrivingModuleService;
use Database\Seeders\DrivingModuleSeeder;
use Database\Seeders\LicenseTypeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DrivingPracticeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(\App\Http\Middleware\EnsureTwoFactorAuthenticated::class);
    }

    // ─── Helper privati ────────────────────────────────────────────────────────

    private function makeAdmin(): User
    {
        return User::factory()->create(['role' => User::ROLE_ADMIN]);
    }

    private function makeInstructor(): User
    {
        return User::factory()->create(['role' => User::ROLE_INSTRUCTOR]);
    }

    private function makeViewer(): User
    {
        return User::factory()->create(['role' => User::ROLE_VIEWER]);
    }

    private function makeEditor(): User
    {
        return User::factory()->create(['role' => User::ROLE_EDITOR]);
    }

    private function makeLicenseTypeB(): LicenseType
    {
        // La migration 2026_06_05_000004 inserisce già la Patente B nel DB di test.
        // Usa firstOrCreate per evitare UniqueConstraintViolationException su code='B'.
        return LicenseType::firstOrCreate(
            ['code' => 'B'],
            [
                'name'            => 'Patente B',
                'description'     => null,
                'exam_questions'  => 30,
                'exam_minutes'    => 20,
                'exam_max_errors' => 3,
                'sort_order'      => 5,
                'is_active'       => true,
            ]
        );
    }

    private function makeModule(LicenseType $lt, string $code = 'A'): DrivingModule
    {
        return DrivingModule::create([
            'license_type_id' => $lt->id,
            'code'            => $code,
            'name'            => "Modulo {$code} – Test",
            'description'     => null,
            'required_hours'  => 2.0,
            'sort_order'      => 1,
        ]);
    }

    // ─── Test seeder ───────────────────────────────────────────────────────────

    public function test_seeder_inserts_4_modules_for_license_type_b(): void
    {
        $this->seed(LicenseTypeSeeder::class);
        $this->seed(DrivingModuleSeeder::class);

        $lt = LicenseType::where('code', 'B')->first();

        $this->assertNotNull($lt);
        $this->assertDatabaseCount('driving_modules', 4);
        $this->assertDatabaseHas('driving_modules', [
            'license_type_id' => $lt->id,
            'code'            => 'A',
        ]);
        $this->assertDatabaseHas('driving_modules', [
            'license_type_id' => $lt->id,
            'code'            => 'D',
        ]);
    }

    // ─── CRUD moduli (admin) ───────────────────────────────────────────────────

    public function test_admin_can_create_module(): void
    {
        $admin = $this->makeAdmin();
        $lt    = $this->makeLicenseTypeB();

        $response = $this->actingAs($admin)->post('/admin/driving-modules', [
            'license_type_id' => $lt->id,
            'code'            => 'A',
            'name'            => 'Modulo A – Test',
            'description'     => null,
            'required_hours'  => 2.0,
            'sort_order'      => 1,
        ]);

        $response->assertRedirect(route('admin.driving-modules.index'));
        $this->assertDatabaseHas('driving_modules', [
            'license_type_id' => $lt->id,
            'code'            => 'A',
            'name'            => 'Modulo A – Test',
        ]);
    }

    public function test_admin_can_update_module(): void
    {
        $admin  = $this->makeAdmin();
        $lt     = $this->makeLicenseTypeB();
        $module = $this->makeModule($lt);

        $response = $this->actingAs($admin)->put("/admin/driving-modules/{$module->id}", [
            'code'           => 'A',
            'name'           => 'Modulo A aggiornato',
            'description'    => 'Nuova descrizione',
            'required_hours' => 3.0,
            'sort_order'     => 1,
        ]);

        $response->assertRedirect(route('admin.driving-modules.index'));
        $this->assertDatabaseHas('driving_modules', [
            'id'   => $module->id,
            'name' => 'Modulo A aggiornato',
        ]);
    }

    public function test_admin_cannot_delete_module_with_sessions(): void
    {
        $admin   = $this->makeAdmin();
        $lt      = $this->makeLicenseTypeB();
        $module  = $this->makeModule($lt);
        $student = $this->makeViewer();

        // Crea una sessione che blocca l'eliminazione del modulo
        DrivingSession::create([
            'student_id'        => $student->id,
            'instructor_id'     => null,
            'driving_module_id' => $module->id,
            'conducted_at'      => now()->toDateString(),
            'duration_minutes'  => 60,
            'notes'             => null,
            'recorded_by'       => $admin->id,
        ]);

        $response = $this->actingAs($admin)->delete("/admin/driving-modules/{$module->id}");

        // Il service lancia RuntimeException → controller flash error e redirect back
        $response->assertSessionHas('error');
        $this->assertDatabaseHas('driving_modules', ['id' => $module->id]);
    }

    public function test_admin_can_delete_module_without_sessions(): void
    {
        $admin  = $this->makeAdmin();
        $lt     = $this->makeLicenseTypeB();
        $module = $this->makeModule($lt);

        $response = $this->actingAs($admin)->delete("/admin/driving-modules/{$module->id}");

        $response->assertRedirect(route('admin.driving-modules.index'));
        $this->assertDatabaseMissing('driving_modules', ['id' => $module->id]);
    }

    public function test_editor_gets_403_on_driving_modules(): void
    {
        $editor = $this->makeEditor();

        $response = $this->actingAs($editor)->get('/admin/driving-modules');

        $response->assertForbidden();
    }

    // ─── Registrazione sessioni ────────────────────────────────────────────────

    public function test_instructor_can_register_session_for_assigned_student(): void
    {
        $instructor = $this->makeInstructor();
        $student    = $this->makeViewer();
        $lt         = $this->makeLicenseTypeB();
        $module     = $this->makeModule($lt);

        // Assegna lo studente all'istruttore tramite pivot
        $instructor->students()->attach($student->id);

        $response = $this->actingAs($instructor)->post("/driving/students/{$student->id}/sessions", [
            'driving_module_id' => $module->id,
            'conducted_at'      => now()->toDateString(),
            'duration_minutes'  => 60,
            'notes'             => 'Prima lezione ok.',
        ]);

        $response->assertSessionHas('success');
        $this->assertDatabaseHas('driving_sessions', [
            'student_id'        => $student->id,
            'driving_module_id' => $module->id,
            'duration_minutes'  => 60,
        ]);
    }

    public function test_instructor_cannot_register_session_for_unassigned_student(): void
    {
        $instructor = $this->makeInstructor();
        $student    = $this->makeViewer();
        $lt         = $this->makeLicenseTypeB();
        $module     = $this->makeModule($lt);

        // Nessuna assegnazione istruttore-studente

        $response = $this->actingAs($instructor)->post("/driving/students/{$student->id}/sessions", [
            'driving_module_id' => $module->id,
            'conducted_at'      => now()->toDateString(),
            'duration_minutes'  => 60,
        ]);

        $response->assertForbidden();
    }

    public function test_viewer_gets_403_trying_to_register_session(): void
    {
        $viewer  = $this->makeViewer();
        $student = $this->makeViewer();
        $lt      = $this->makeLicenseTypeB();
        $module  = $this->makeModule($lt);

        // Il viewer non ha accesso alla route protetta con role:admin,instructor
        $response = $this->actingAs($viewer)->post("/driving/students/{$student->id}/sessions", [
            'driving_module_id' => $module->id,
            'conducted_at'      => now()->toDateString(),
            'duration_minutes'  => 60,
        ]);

        // RoleMiddleware ritorna 403 per i viewer
        $response->assertForbidden();
    }

    // ─── Calcolo avanzamento ───────────────────────────────────────────────────

    public function test_get_progress_calculates_correctly(): void
    {
        $admin   = $this->makeAdmin();
        $student = $this->makeViewer();
        $lt      = $this->makeLicenseTypeB();

        // Modulo con 2h richieste
        $module = DrivingModule::create([
            'license_type_id' => $lt->id,
            'code'            => 'A',
            'name'            => 'Modulo A',
            'description'     => null,
            'required_hours'  => 2.0,
            'sort_order'      => 1,
        ]);

        // 90 min + 45 min = 135 min = 2.25h → completato (>= 2h)
        DrivingSession::create([
            'student_id'        => $student->id,
            'instructor_id'     => null,
            'driving_module_id' => $module->id,
            'conducted_at'      => now()->toDateString(),
            'duration_minutes'  => 90,
            'notes'             => null,
            'recorded_by'       => $admin->id,
        ]);
        DrivingSession::create([
            'student_id'        => $student->id,
            'instructor_id'     => null,
            'driving_module_id' => $module->id,
            'conducted_at'      => now()->subDay()->toDateString(),
            'duration_minutes'  => 45,
            'notes'             => null,
            'recorded_by'       => $admin->id,
        ]);

        $service  = app(\App\Services\DrivingSessionService::class);
        $progress = $service->getProgress($student, $lt);

        $moduleData = $progress['modules'][0];

        $this->assertTrue($moduleData['completed']);
        // round(135/60, 1) = round(2.25, 1) = 2.3 in PHP (arrotondamento a 1 decimale)
        $this->assertEquals(2.3, $moduleData['completed_hours']);
        $this->assertEquals(2, $moduleData['sessions_count']);
        $this->assertEquals(100, $progress['percentage']);
        $this->assertTrue($progress['all_completed']);
    }

    // ─── Vincoli FK ───────────────────────────────────────────────────────────

    public function test_cascade_delete_student_removes_sessions(): void
    {
        $admin   = $this->makeAdmin();
        $student = $this->makeViewer();
        $lt      = $this->makeLicenseTypeB();
        $module  = $this->makeModule($lt);

        $session = DrivingSession::create([
            'student_id'        => $student->id,
            'instructor_id'     => null,
            'driving_module_id' => $module->id,
            'conducted_at'      => now()->toDateString(),
            'duration_minutes'  => 60,
            'notes'             => null,
            'recorded_by'       => $admin->id,
        ]);

        // CASCADE: eliminando lo studente le sue sessioni devono sparire
        $student->delete();

        $this->assertDatabaseMissing('driving_sessions', ['id' => $session->id]);
    }

    public function test_null_on_delete_instructor_nullifies_instructor_id(): void
    {
        $admin      = $this->makeAdmin();
        $instructor = $this->makeInstructor();
        $student    = $this->makeViewer();
        $lt         = $this->makeLicenseTypeB();
        $module     = $this->makeModule($lt);

        $session = DrivingSession::create([
            'student_id'        => $student->id,
            'instructor_id'     => $instructor->id,
            'driving_module_id' => $module->id,
            'conducted_at'      => now()->toDateString(),
            'duration_minutes'  => 60,
            'notes'             => null,
            'recorded_by'       => $admin->id,
        ]);

        // SET NULL: eliminando l'istruttore, instructor_id diventa null
        $instructor->delete();

        $this->assertDatabaseHas('driving_sessions', [
            'id'            => $session->id,
            'instructor_id' => null,
        ]);
    }

    public function test_restrict_cannot_delete_module_with_sessions(): void
    {
        $admin   = $this->makeAdmin();
        $student = $this->makeViewer();
        $lt      = $this->makeLicenseTypeB();
        $module  = $this->makeModule($lt);

        DrivingSession::create([
            'student_id'        => $student->id,
            'instructor_id'     => null,
            'driving_module_id' => $module->id,
            'conducted_at'      => now()->toDateString(),
            'duration_minutes'  => 60,
            'notes'             => null,
            'recorded_by'       => $admin->id,
        ]);

        // Il service deve sollevare RuntimeException prima che il DB dia errore RESTRICT
        $service = app(\App\Services\DrivingModuleService::class);

        $this->expectException(\RuntimeException::class);

        $service->delete($module);
    }

    // ─── Validazione ──────────────────────────────────────────────────────────

    public function test_duration_minutes_validation_max_120(): void
    {
        $instructor = $this->makeInstructor();
        $student    = $this->makeViewer();
        $lt         = $this->makeLicenseTypeB();
        $module     = $this->makeModule($lt);

        // Assegna lo studente per passare il check canRegisterForStudent
        $instructor->students()->attach($student->id);

        $response = $this->actingAs($instructor)->post("/driving/students/{$student->id}/sessions", [
            'driving_module_id' => $module->id,
            'conducted_at'      => now()->toDateString(),
            'duration_minutes'  => 121,
        ]);

        $response->assertSessionHasErrors('duration_minutes');
    }
}
