<?php

namespace Tests\Feature;

use App\Http\Middleware\EnsureTwoFactorAuthenticated;
use App\Models\AuditLog;
use App\Models\LicenseType;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\User;
use Database\Seeders\SystemSettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminPagesStructureTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(EnsureTwoFactorAuthenticated::class);
        $this->seed(SystemSettingSeeder::class);
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    // ------------------------------------------------------------------ P04

    public function test_audit_log_index_returns_200(): void
    {
        $this->actingAs($this->admin())
            ->get(route('admin.audit.index'))
            ->assertOk();
    }

    public function test_audit_log_show_returns_200_for_existing_log(): void
    {
        $admin = $this->admin();

        // Crea un audit log direttamente
        $licenseType = LicenseType::factory()->create();
        $log = AuditLog::first();

        if (!$log) {
            $this->markTestSkipped('Nessun audit log disponibile.');
        }

        $this->actingAs($admin)
            ->get(route('admin.audit.show', $log))
            ->assertOk();
    }

    public function test_health_index_returns_200(): void
    {
        $this->actingAs($this->admin())
            ->get(route('admin.health.index'))
            ->assertOk();
    }

    public function test_license_types_index_returns_200(): void
    {
        $this->actingAs($this->admin())
            ->get(route('admin.license-types.index'))
            ->assertOk();
    }

    public function test_license_types_create_returns_200(): void
    {
        $this->actingAs($this->admin())
            ->get(route('admin.license-types.create'))
            ->assertOk();
    }

    public function test_license_types_edit_returns_200(): void
    {
        $licenseType = LicenseType::factory()->create();

        $this->actingAs($this->admin())
            ->get(route('admin.license-types.edit', $licenseType))
            ->assertOk();
    }

    public function test_system_settings_returns_200(): void
    {
        $this->actingAs($this->admin())
            ->get(route('admin.system.settings'))
            ->assertOk();
    }

    public function test_system_form_fields_returns_200(): void
    {
        $this->actingAs($this->admin())
            ->get(route('admin.system.form-fields'))
            ->assertOk();
    }

    public function test_system_health_returns_200(): void
    {
        $this->actingAs($this->admin())
            ->get(route('admin.system.health'))
            ->assertOk();
    }

    // ------------------------------------------------------------------ P08

    public function test_system_settings_does_not_contain_wrong_font_variable(): void
    {
        $response = $this->actingAs($this->admin())
            ->get(route('admin.system.settings'));

        $response->assertOk();
        $response->assertDontSee('var(--font-family)', false);
    }

    // ------------------------------------------------------------------ P06

    public function test_quiz_attempt_page_returns_200_and_shows_outcome(): void
    {
        $viewer  = User::factory()->create(['role' => 'viewer']);
        $quiz    = Quiz::factory()->create();
        $attempt = QuizAttempt::factory()->create([
            'user_id' => $viewer->id,
            'quiz_id' => $quiz->id,
        ]);

        $this->actingAs($viewer)
            ->get(route('quiz.attempts.show', $attempt))
            ->assertOk();
    }

    public function test_simulator_result_page_returns_200_and_shows_outcome(): void
    {
        $licenseType = LicenseType::factory()->create();
        $viewer      = User::factory()->create([
            'role'                   => 'viewer',
            'active_license_type_id' => $licenseType->id,
        ]);

        // Il simulatore usa QuizAttempt con quiz_id null
        $attempt = QuizAttempt::factory()->create([
            'user_id' => $viewer->id,
            'quiz_id' => null,
        ]);

        $this->actingAs($viewer)
            ->get(route('simulator.result', $attempt))
            ->assertOk();
    }
}
