<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\DrivingModule;
use App\Models\DrivingSession;
use App\Models\LicenseType;
use App\Models\User;
use App\Services\DrivingAttestationService;
use App\Services\DrivingSessionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DrivingAttestationTest extends TestCase
{
    use RefreshDatabase;

    protected DrivingAttestationService $attestationService;
    protected DrivingSessionService $sessionService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(\App\Http\Middleware\EnsureTwoFactorAuthenticated::class);
        $this->attestationService = app(DrivingAttestationService::class);
        $this->sessionService = app(DrivingSessionService::class);
    }

    // ─── Helpers ────────────────────────────────────────────────────────────────

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
        return User::factory()->create(['role' => User::ROLE_VIEWER, 'active_license_type_id' => null]);
    }

    private function makeLicenseTypeB(): LicenseType
    {
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

    private function makeModule(LicenseType $lt, string $code, float $hours): DrivingModule
    {
        return DrivingModule::create([
            'license_type_id' => $lt->id,
            'code'            => $code,
            'name'            => "Modulo {$code}",
            'required_hours'  => $hours,
            'sort_order'      => 1,
        ]);
    }

    private function createSessionsForStudent(User $student, DrivingModule $module, int $hours): void
    {
        $this->sessionService->record([
            'student_id'          => $student->id,
            'instructor_id'       => null, // simulate anonimizzazione
            'driving_module_id'   => $module->id,
            'conducted_at'        => now()->subDay(),
            'duration_minutes'    => $hours * 60,
            'notes'               => 'Test session',
        ]);
    }

    // ─── Test: Authorization ───────────────────────────────────────────────────

    public function test_admin_can_download_any_student_attestation(): void
    {
        $admin = $this->makeAdmin();
        $student = $this->makeViewer();
        $lt = $this->makeLicenseTypeB();
        $student->update(['active_license_type_id' => $lt->id]);

        $module = $this->makeModule($lt, 'A', 2);
        $this->createSessionsForStudent($student, $module, 2);

        $response = $this->actingAs($admin)
            ->get(route('driving.attestation.download', $student));

        $response->assertStatus(200);
        $response->assertHeader('content-type', 'application/pdf');
    }

    public function test_instructor_can_download_assigned_student_attestation(): void
    {
        $instructor = $this->makeInstructor();
        $student = $this->makeViewer();
        $lt = $this->makeLicenseTypeB();
        $student->update(['active_license_type_id' => $lt->id]);

        // Assign student to instructor
        $instructor->students()->attach($student);

        $module = $this->makeModule($lt, 'A', 2);
        $this->createSessionsForStudent($student, $module, 2);

        $response = $this->actingAs($instructor)
            ->get(route('driving.attestation.download', $student));

        $response->assertStatus(200);
        $response->assertHeader('content-type', 'application/pdf');
    }

    public function test_instructor_cannot_download_unassigned_student_attestation(): void
    {
        $instructor = $this->makeInstructor();
        $student = $this->makeViewer();
        $lt = $this->makeLicenseTypeB();
        $student->update(['active_license_type_id' => $lt->id]);

        $module = $this->makeModule($lt, 'A', 2);
        $this->createSessionsForStudent($student, $module, 2);

        $response = $this->actingAs($instructor)
            ->get(route('driving.attestation.download', $student));

        $response->assertStatus(403);
    }

    public function test_viewer_can_download_own_attestation_when_completed(): void
    {
        $student = $this->makeViewer();
        $lt = $this->makeLicenseTypeB();
        $student->update(['active_license_type_id' => $lt->id]);

        $module = $this->makeModule($lt, 'A', 2);
        $this->createSessionsForStudent($student, $module, 2); // 2 hours = completed

        $response = $this->actingAs($student)
            ->get(route('driving.attestation.download', $student));

        $response->assertStatus(200);
        $response->assertHeader('content-type', 'application/pdf');
    }

    public function test_viewer_cannot_download_attestation_when_incomplete(): void
    {
        $student = $this->makeViewer();
        $lt = $this->makeLicenseTypeB();
        $student->update(['active_license_type_id' => $lt->id]);

        $module = $this->makeModule($lt, 'A', 2);
        $this->createSessionsForStudent($student, $module, 1); // 1 hour < 2 required

        $response = $this->actingAs($student)
            ->get(route('driving.attestation.download', $student));

        $response->assertStatus(403);
    }

    public function test_viewer_cannot_download_other_student_attestation(): void
    {
        $student1 = $this->makeViewer();
        $student2 = $this->makeViewer();
        $lt = $this->makeLicenseTypeB();
        $student1->update(['active_license_type_id' => $lt->id]);
        $student2->update(['active_license_type_id' => $lt->id]);

        $module = $this->makeModule($lt, 'A', 2);
        $this->createSessionsForStudent($student1, $module, 2);

        $response = $this->actingAs($student2)
            ->get(route('driving.attestation.download', $student1));

        $response->assertStatus(403);
    }

    // ─── Test: Service behavior ─────────────────────────────────────────────────

    public function test_build_data_handles_null_instructor(): void
    {
        $student = $this->makeViewer();
        $lt = $this->makeLicenseTypeB();
        $student->update(['active_license_type_id' => $lt->id]);

        $module = $this->makeModule($lt, 'A', 2);
        $this->createSessionsForStudent($student, $module, 2);

        $data = $this->attestationService->buildData($student, $lt);

        $this->assertIsArray($data);
        $this->assertEquals($student->name, $data['student']['name']);
        $this->assertInstanceOf(Collection::class, $data['instructors']);
        $this->assertEquals(0, $data['instructors']->count()); // null instructor not included
    }

    public function test_pdf_content_includes_disclaimer(): void
    {
        $student = $this->makeViewer();
        $lt = $this->makeLicenseTypeB();
        $student->update(['active_license_type_id' => $lt->id]);

        $module = $this->makeModule($lt, 'A', 2);
        $this->createSessionsForStudent($student, $module, 2);

        $path = $this->attestationService->generatePdf($student, $lt);

        $this->assertTrue(Storage::disk('local')->exists(str_replace(Storage::disk('local')->path(''), '', $path)));

        // Clean up
        Storage::disk('local')->delete(str_replace(Storage::disk('local')->path(''), '', $path));
    }

    public function test_cleanup_command_removes_old_files(): void
    {
        $student = $this->makeViewer();
        $lt = $this->makeLicenseTypeB();
        $student->update(['active_license_type_id' => $lt->id]);

        $module = $this->makeModule($lt, 'A', 2);
        $this->createSessionsForStudent($student, $module, 2);

        $path1 = $this->attestationService->generatePdf($student, $lt);
        $relPath1 = str_replace(Storage::disk('local')->path(''), '', $path1);

        // Run cleanup command
        $this->artisan('driving:cleanup-attestations')
            ->assertExitCode(0);

        // Recent file still exists
        $this->assertTrue(Storage::disk('local')->exists($relPath1));
    }

    // ─── Test: Audit log ────────────────────────────────────────────────────────

    public function test_download_records_audit_log(): void
    {
        $admin = $this->makeAdmin();
        $student = $this->makeViewer();
        $lt = $this->makeLicenseTypeB();
        $student->update(['active_license_type_id' => $lt->id]);

        $module = $this->makeModule($lt, 'A', 2);
        $this->createSessionsForStudent($student, $module, 2);

        $this->actingAs($admin)
            ->get(route('driving.attestation.download', $student));

        $audit = AuditLog::where('event', 'export_driving_attestation')
            ->where('model_id', $student->id)
            ->latest()
            ->first();

        $this->assertNotNull($audit);
        $this->assertEquals($lt->id, $audit->new_values['license_type_id']);
        $this->assertEquals($admin->id, $audit->new_values['exported_by']);
    }
}
