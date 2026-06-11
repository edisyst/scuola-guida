<?php

namespace Tests\Feature;

use App\Models\DrivingModule;
use App\Models\DrivingSession;
use App\Models\LicenseType;
use App\Models\User;
use App\Services\DrivingSessionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DrivingSequentialityTest extends TestCase
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

    private function makeStudent(LicenseType $lt): User
    {
        $student = User::factory()->create(['role' => User::ROLE_VIEWER]);
        $student->update(['active_license_type_id' => $lt->id]);
        return $student;
    }

    private function makeLicenseType(): LicenseType
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

    private function makeModule(LicenseType $lt, string $code, int $sortOrder, float $requiredHours = 2.0): DrivingModule
    {
        return DrivingModule::create([
            'license_type_id' => $lt->id,
            'code'            => $code,
            'name'            => "Modulo {$code}",
            'description'     => null,
            'required_hours'  => $requiredHours,
            'sort_order'      => $sortOrder,
        ]);
    }

    private function recordSession(User $student, DrivingModule $module, int $minutes, User $actor): DrivingSession
    {
        return DrivingSession::create([
            'student_id'        => $student->id,
            'driving_module_id' => $module->id,
            'instructor_id'     => null,
            'conducted_at'      => now()->toDateString(),
            'duration_minutes'  => $minutes,
            'notes'             => null,
            'recorded_by'       => $actor->id,
        ]);
    }

    // ─── Vincolo di sequenzialità — controller ────────────────────────────────

    public function test_instructor_gets_422_registering_module_b_without_completing_a(): void
    {
        $instructor = $this->makeInstructor();
        $lt         = $this->makeLicenseType();
        $student    = $this->makeStudent($lt);

        $moduleA = $this->makeModule($lt, 'A', 1, 2.0);
        $moduleB = $this->makeModule($lt, 'B', 2, 2.0);

        $instructor->students()->attach($student->id);

        $response = $this->actingAs($instructor)->post("/driving/students/{$student->id}/sessions", [
            'driving_module_id' => $moduleB->id,
            'conducted_at'      => now()->toDateString(),
            'duration_minutes'  => 60,
        ]);

        $response->assertStatus(422);
        $this->assertDatabaseMissing('driving_sessions', ['driving_module_id' => $moduleB->id]);
    }

    public function test_instructor_gets_422_registering_module_d_without_completing_a_b_c(): void
    {
        $instructor = $this->makeInstructor();
        $lt         = $this->makeLicenseType();
        $student    = $this->makeStudent($lt);

        $this->makeModule($lt, 'A', 1, 2.0);
        $this->makeModule($lt, 'B', 2, 2.0);
        $this->makeModule($lt, 'C', 3, 2.0);
        $moduleD = $this->makeModule($lt, 'D', 4, 2.0);

        $instructor->students()->attach($student->id);

        $response = $this->actingAs($instructor)->post("/driving/students/{$student->id}/sessions", [
            'driving_module_id' => $moduleD->id,
            'conducted_at'      => now()->toDateString(),
            'duration_minutes'  => 60,
        ]);

        $response->assertStatus(422);
    }

    public function test_instructor_gets_422_registering_d_when_only_a_b_completed(): void
    {
        $admin      = $this->makeAdmin();
        $instructor = $this->makeInstructor();
        $lt         = $this->makeLicenseType();
        $student    = $this->makeStudent($lt);

        $moduleA = $this->makeModule($lt, 'A', 1, 1.0);
        $moduleB = $this->makeModule($lt, 'B', 2, 1.0);
        $this->makeModule($lt, 'C', 3, 1.0);
        $moduleD = $this->makeModule($lt, 'D', 4, 1.0);

        $instructor->students()->attach($student->id);

        // Completa A e B
        $this->recordSession($student, $moduleA, 60, $admin);
        $this->recordSession($student, $moduleB, 60, $admin);

        $response = $this->actingAs($instructor)->post("/driving/students/{$student->id}/sessions", [
            'driving_module_id' => $moduleD->id,
            'conducted_at'      => now()->toDateString(),
            'duration_minutes'  => 60,
        ]);

        $response->assertStatus(422);
    }

    public function test_once_a_completed_b_becomes_registrable(): void
    {
        $admin      = $this->makeAdmin();
        $instructor = $this->makeInstructor();
        $lt         = $this->makeLicenseType();
        $student    = $this->makeStudent($lt);

        $moduleA = $this->makeModule($lt, 'A', 1, 1.0);
        $moduleB = $this->makeModule($lt, 'B', 2, 1.0);

        $instructor->students()->attach($student->id);

        // Completa A: 60 min = 1h = 100%
        $this->recordSession($student, $moduleA, 60, $admin);

        $response = $this->actingAs($instructor)->post("/driving/students/{$student->id}/sessions", [
            'driving_module_id' => $moduleB->id,
            'conducted_at'      => now()->toDateString(),
            'duration_minutes'  => 60,
        ]);

        $response->assertSessionHas('success');
        $this->assertDatabaseHas('driving_sessions', [
            'student_id'        => $student->id,
            'driving_module_id' => $moduleB->id,
        ]);
    }

    public function test_first_module_always_registrable(): void
    {
        $instructor = $this->makeInstructor();
        $lt         = $this->makeLicenseType();
        $student    = $this->makeStudent($lt);

        $moduleA = $this->makeModule($lt, 'A', 1, 2.0);

        $instructor->students()->attach($student->id);

        $response = $this->actingAs($instructor)->post("/driving/students/{$student->id}/sessions", [
            'driving_module_id' => $moduleA->id,
            'conducted_at'      => now()->toDateString(),
            'duration_minutes'  => 60,
        ]);

        $response->assertSessionHas('success');
    }

    // ─── getCompletionStatus ──────────────────────────────────────────────────

    public function test_get_completion_status_all_completed_true_when_all_hours_met(): void
    {
        $admin   = $this->makeAdmin();
        $lt      = $this->makeLicenseType();
        $student = $this->makeStudent($lt);

        $moduleA = $this->makeModule($lt, 'A', 1, 1.0);
        $moduleB = $this->makeModule($lt, 'B', 2, 1.0);

        $this->recordSession($student, $moduleA, 60, $admin);
        $this->recordSession($student, $moduleB, 60, $admin);

        $service = app(DrivingSessionService::class);
        $status  = $service->getCompletionStatus($student, $lt);

        $this->assertTrue($status['all_completed']);
        $this->assertEquals(2, count($status['completed_modules']));
        $this->assertNull($status['next_required_module_id']);
        $this->assertEquals(100, $status['percentage']);
    }

    public function test_get_completion_status_all_completed_false_when_partial(): void
    {
        $admin   = $this->makeAdmin();
        $lt      = $this->makeLicenseType();
        $student = $this->makeStudent($lt);

        $moduleA = $this->makeModule($lt, 'A', 1, 2.0);
        $moduleB = $this->makeModule($lt, 'B', 2, 2.0);

        // Solo 30 min su 2h richieste per A
        $this->recordSession($student, $moduleA, 30, $admin);

        $service = app(DrivingSessionService::class);
        $status  = $service->getCompletionStatus($student, $lt);

        $this->assertFalse($status['all_completed']);
        $this->assertEquals($moduleA->id, $status['next_required_module_id']);
        $this->assertNull($status['completion_date']);
    }

    public function test_get_completion_status_with_no_sessions_returns_first_module_as_next(): void
    {
        $lt      = $this->makeLicenseType();
        $student = $this->makeStudent($lt);

        $moduleA = $this->makeModule($lt, 'A', 1, 2.0);
        $this->makeModule($lt, 'B', 2, 2.0);

        $service = app(DrivingSessionService::class);
        $status  = $service->getCompletionStatus($student, $lt);

        $this->assertFalse($status['all_completed']);
        $this->assertEquals($moduleA->id, $status['next_required_module_id']);
        $this->assertEquals(0, $status['percentage']);
    }

    public function test_completion_date_is_the_session_that_reached_100_percent(): void
    {
        $admin   = $this->makeAdmin();
        $lt      = $this->makeLicenseType();
        $student = $this->makeStudent($lt);

        $module = $this->makeModule($lt, 'A', 1, 1.0);

        $targetDate = now()->subDays(3)->toDateString();

        DrivingSession::create([
            'student_id'        => $student->id,
            'driving_module_id' => $module->id,
            'instructor_id'     => null,
            'conducted_at'      => $targetDate,
            'duration_minutes'  => 60,
            'notes'             => null,
            'recorded_by'       => $admin->id,
        ]);

        $service = app(DrivingSessionService::class);
        $status  = $service->getCompletionStatus($student, $lt);

        $this->assertTrue($status['all_completed']);
        $this->assertNotNull($status['completion_date']);
        $this->assertEquals($targetDate, $status['completion_date']->toDateString());
    }

    public function test_completion_date_is_session_crossing_threshold_not_last_session(): void
    {
        $admin   = $this->makeAdmin();
        $lt      = $this->makeLicenseType();
        $student = $this->makeStudent($lt);

        $module = $this->makeModule($lt, 'A', 1, 1.0); // 1h richiesta

        $completingDate = now()->subDays(5)->toDateString();
        $laterDate      = now()->subDays(2)->toDateString();

        // Sessione che completa il modulo (60 min = 1h)
        DrivingSession::create([
            'student_id'        => $student->id,
            'driving_module_id' => $module->id,
            'instructor_id'     => null,
            'conducted_at'      => $completingDate,
            'duration_minutes'  => 60,
            'notes'             => null,
            'recorded_by'       => $admin->id,
        ]);

        // Sessione extra dopo il completamento
        DrivingSession::create([
            'student_id'        => $student->id,
            'driving_module_id' => $module->id,
            'instructor_id'     => null,
            'conducted_at'      => $laterDate,
            'duration_minutes'  => 30,
            'notes'             => null,
            'recorded_by'       => $admin->id,
        ]);

        $service = app(DrivingSessionService::class);
        $status  = $service->getCompletionStatus($student, $lt);

        // La data deve essere quella della sessione che ha raggiunto 1h, non quella extra
        $this->assertEquals($completingDate, $status['completion_date']->toDateString());
    }

    // ─── View viewer ─────────────────────────────────────────────────────────

    public function test_viewer_sees_certification_unlocked_when_all_completed(): void
    {
        $admin   = $this->makeAdmin();
        $lt      = $this->makeLicenseType();
        $student = $this->makeStudent($lt);

        $module = $this->makeModule($lt, 'A', 1, 1.0);
        $this->recordSession($student, $module, 60, $admin);

        $response = $this->actingAs($student)->get('/driving/progress');

        $response->assertStatus(200);
        $response->assertSee(__('driving.cert_status_title'));
        $response->assertSee(__('driving.cert_unlocked_on', ['date' => now()->toDateString() !== null ? now()->format('d/m/Y') : '—']));
    }

    public function test_viewer_sees_in_progress_when_not_completed(): void
    {
        $lt      = $this->makeLicenseType();
        $student = $this->makeStudent($lt);

        $this->makeModule($lt, 'A', 1, 2.0);

        $response = $this->actingAs($student)->get('/driving/progress');

        $response->assertStatus(200);
        $response->assertSee(__('driving.cert_status_title'));
    }

    // ─── PDF attestazione ─────────────────────────────────────────────────────

    public function test_pdf_attestation_includes_certification_status(): void
    {
        $admin   = $this->makeAdmin();
        $lt      = $this->makeLicenseType();
        $student = $this->makeStudent($lt);

        $module = $this->makeModule($lt, 'A', 1, 1.0);
        $this->recordSession($student, $module, 60, $admin);

        // Verifica che buildData includa completion_status
        $service = app(\App\Services\DrivingAttestationService::class);
        $data    = $service->buildData($student, $lt);

        $this->assertArrayHasKey('completion_status', $data);
        $this->assertTrue($data['completion_status']['all_completed']);
        $this->assertNotNull($data['completion_status']['completion_date']);
    }

    public function test_pdf_attestation_includes_in_progress_status_when_not_complete(): void
    {
        $lt      = $this->makeLicenseType();
        $student = $this->makeStudent($lt);

        $this->makeModule($lt, 'A', 1, 2.0);

        $service = app(\App\Services\DrivingAttestationService::class);
        $data    = $service->buildData($student, $lt);

        $this->assertArrayHasKey('completion_status', $data);
        $this->assertFalse($data['completion_status']['all_completed']);
        $this->assertNull($data['completion_status']['completion_date']);
    }
}
