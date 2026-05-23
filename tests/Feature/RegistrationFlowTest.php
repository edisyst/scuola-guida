<?php

namespace Tests\Feature;

use App\Models\Quiz;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class RegistrationFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(\App\Http\Middleware\EnsureTwoFactorAuthenticated::class);
    }

    public function test_viewer_sees_registration_form_in_profile(): void
    {
        $viewer = User::factory()->create(['role' => User::ROLE_VIEWER]);

        $this->actingAs($viewer)
            ->get('/profile')
            ->assertOk()
            ->assertSee('Iscrizione esami ufficiali');
    }

    public function test_admin_does_not_see_registration_form(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $this->actingAs($admin)
            ->get('/profile')
            ->assertOk()
            ->assertDontSee('Iscrizione esami ufficiali');
    }

    public function test_viewer_can_submit_registration_data(): void
    {
        Storage::fake('public');

        $viewer = User::factory()->create(['role' => User::ROLE_VIEWER]);

        $response = $this->actingAs($viewer)
            ->post('/profile/registration', [
                'first_name'  => 'Mario',
                'last_name'   => 'Rossi',
                'address'     => 'Via Roma 1, Milano',
                'birth_date'  => '1995-06-15',
                'birth_place' => 'Milano',
                'fiscal_code' => 'RSSMRA95H15F205X',
                'id_document' => UploadedFile::fake()->create('id.pdf', 100, 'application/pdf'),
            ]);

        $response->assertRedirect('/profile');

        $viewer->refresh();
        $this->assertSame('Mario', $viewer->first_name);
        $this->assertSame('RSSMRA95H15F205X', $viewer->fiscal_code);
        $this->assertSame(User::REG_PENDING, $viewer->registration_status);
        $this->assertNotNull($viewer->registration_submitted_at);
        $this->assertNotNull($viewer->id_document_path);
        Storage::disk('public')->assertExists($viewer->id_document_path);
    }

    public function test_registration_requires_all_personal_data(): void
    {
        $viewer = User::factory()->create(['role' => User::ROLE_VIEWER]);

        $this->actingAs($viewer)
            ->from('/profile')
            ->post('/profile/registration', [])
            ->assertSessionHasErrors([
                'first_name', 'last_name', 'address',
                'birth_date', 'birth_place', 'fiscal_code', 'id_document',
            ]);

        $this->assertSame(User::REG_NONE, $viewer->fresh()->registration_status);
    }

    public function test_admin_can_approve_registration(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $viewer = User::factory()->create([
            'role' => User::ROLE_VIEWER,
            'first_name' => 'Mario',
            'last_name' => 'Rossi',
            'registration_status' => User::REG_PENDING,
            'registration_submitted_at' => now(),
        ]);

        $this->actingAs($admin)
            ->post("/admin/registrations/{$viewer->id}/approve")
            ->assertRedirect(route('admin.registrations.index'));

        $viewer->refresh();
        $this->assertSame(User::REG_APPROVED, $viewer->registration_status);
        $this->assertSame($admin->id, $viewer->registration_reviewed_by);
        $this->assertNotNull($viewer->registration_reviewed_at);
        $this->assertTrue($viewer->canEnrollOfficialExams());
    }

    public function test_admin_can_reject_registration_with_reason(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $viewer = User::factory()->create([
            'role' => User::ROLE_VIEWER,
            'registration_status' => User::REG_PENDING,
            'registration_submitted_at' => now(),
        ]);

        $this->actingAs($admin)
            ->post("/admin/registrations/{$viewer->id}/reject", [
                'reason' => 'Documento illeggibile',
            ])
            ->assertRedirect(route('admin.registrations.index'));

        $viewer->refresh();
        $this->assertSame(User::REG_REJECTED, $viewer->registration_status);
        $this->assertSame('Documento illeggibile', $viewer->registration_rejection_reason);
        $this->assertFalse($viewer->canEnrollOfficialExams());
    }

    public function test_unapproved_viewer_cannot_enroll_in_official_exam(): void
    {
        $viewer = User::factory()->create([
            'role' => User::ROLE_VIEWER,
            'registration_status' => User::REG_NONE,
        ]);

        $quiz = Quiz::create([
            'title'        => 'Esame ufficiale',
            'time_limit'   => 1800,
            'max_errors'   => 4,
            'max_questions'=> 40,
            'status'       => Quiz::STATUS_CONFIRMED,
            'published_at' => now(),
            'confirmed_at' => now(),
        ]);

        $this->actingAs($viewer)
            ->from('/quiz/confirmed')
            ->post("/quiz/{$quiz->id}/enrollments")
            ->assertRedirect('/quiz/confirmed')
            ->assertSessionHas('error');

        $this->assertDatabaseCount('quiz_enrollments', 0);
    }

    public function test_approved_viewer_can_enroll_in_official_exam(): void
    {
        $viewer = User::factory()->create([
            'role' => User::ROLE_VIEWER,
            'registration_status' => User::REG_APPROVED,
        ]);

        $quiz = Quiz::create([
            'title'        => 'Esame ufficiale',
            'time_limit'   => 1800,
            'max_errors'   => 4,
            'max_questions'=> 40,
            'status'       => Quiz::STATUS_CONFIRMED,
            'published_at' => now(),
            'confirmed_at' => now(),
        ]);

        $this->actingAs($viewer)
            ->post("/quiz/{$quiz->id}/enrollments")
            ->assertSessionHas('success');

        $this->assertDatabaseHas('quiz_enrollments', [
            'quiz_id' => $quiz->id,
            'user_id' => $viewer->id,
        ]);
    }

    public function test_resubmitting_data_after_approval_returns_to_pending(): void
    {
        Storage::fake('public');

        $viewer = User::factory()->create([
            'role' => User::ROLE_VIEWER,
            'first_name' => 'Mario',
            'last_name' => 'Rossi',
            'address' => 'Via Vecchia 1',
            'birth_date' => '1995-06-15',
            'birth_place' => 'Milano',
            'fiscal_code' => 'RSSMRA95H15F205X',
            'id_document_path' => 'registrations/old.pdf',
            'registration_status' => User::REG_APPROVED,
            'registration_reviewed_at' => now(),
        ]);

        $this->actingAs($viewer)
            ->post('/profile/registration', [
                'first_name'  => 'Mario',
                'last_name'   => 'Rossi',
                'address'     => 'Via Nuova 99, Roma',
                'birth_date'  => '1995-06-15',
                'birth_place' => 'Milano',
                'fiscal_code' => 'RSSMRA95H15F205X',
                // id_document opzionale: documento già caricato
            ])
            ->assertRedirect('/profile');

        $viewer->refresh();
        $this->assertSame(User::REG_PENDING, $viewer->registration_status);
        $this->assertSame('Via Nuova 99, Roma', $viewer->address);
        $this->assertFalse($viewer->canEnrollOfficialExams());
    }

    public function test_non_admin_cannot_access_admin_registrations(): void
    {
        $viewer = User::factory()->create(['role' => User::ROLE_VIEWER]);

        $this->actingAs($viewer)
            ->get('/admin/registrations')
            ->assertForbidden();
    }
}
