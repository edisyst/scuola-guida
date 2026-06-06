<?php

namespace Tests\Feature;

use App\Models\LicenseType;
use App\Models\Question;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\User;
use App\Services\ReportingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MultiLicenseReportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(\App\Http\Middleware\EnsureTwoFactorAuthenticated::class);
    }

    public function test_quiz_datatable_filtered_by_license_type_returns_only_matching_quizzes(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $typeA = LicenseType::factory()->create(['code' => 'TIPO_A', 'name' => 'Patente A']);
        $typeB = LicenseType::factory()->create(['code' => 'TIPO_B', 'name' => 'Patente B test']);

        Quiz::factory()->create(['license_type_id' => $typeA->id, 'title' => 'Quiz A1']);
        Quiz::factory()->create(['license_type_id' => $typeA->id, 'title' => 'Quiz A2']);
        Quiz::factory()->create(['license_type_id' => $typeB->id, 'title' => 'Quiz B1']);

        $response = $this->actingAs($admin)
            ->get(route('admin.quizzes.index', ['license_type_id' => $typeA->id]));

        $response->assertStatus(200);
        $response->assertSee('Quiz A1');
        $response->assertSee('Quiz A2');
        $response->assertDontSee('Quiz B1');
    }

    public function test_questions_datatable_filtered_by_license_type_returns_only_matching_questions(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $typeA = LicenseType::factory()->create(['code' => 'TIPO_A']);
        $typeB = LicenseType::factory()->create(['code' => 'TIPO_B']);

        $catA = \App\Models\Category::factory()->create();
        $catB = \App\Models\Category::factory()->create();

        $catA->licenseTypes()->attach($typeA);
        $catB->licenseTypes()->attach($typeB);

        Question::factory()->create(['category_id' => $catA->id, 'question' => 'Domanda A']);
        Question::factory()->create(['category_id' => $catB->id, 'question' => 'Domanda B']);

        $url = route('admin.questions.data') . '?' . http_build_query([
            'draw'            => 1,
            'start'           => 0,
            'length'          => 10,
            'license_type_id' => $typeA->id,
        ]);
        $response = $this->actingAs($admin)->get($url);

        $response->assertStatus(200);
        $data = $response->json();
        $this->assertEquals(1, $data['recordsFiltered']);
        $this->assertStringContainsString('Domanda A', json_encode($data['data']));
    }

    public function test_report_service_with_license_type_aggregates_only_that_type_quizzes(): void
    {
        $typeA = LicenseType::factory()->create();
        $typeB = LicenseType::factory()->create();

        $quizA = Quiz::factory()->create(['license_type_id' => $typeA->id, 'status' => 'confirmed']);
        $quizB = Quiz::factory()->create(['license_type_id' => $typeB->id, 'status' => 'confirmed']);

        $user = User::factory()->create(['role' => 'viewer']);

        QuizAttempt::factory()->create([
            'quiz_id'         => $quizA->id,
            'user_id'         => $user->id,
            'score'           => 10,
            'total_questions' => 20,
            'created_at'      => now(),
        ]);
        QuizAttempt::factory()->create([
            'quiz_id'         => $quizB->id,
            'user_id'         => $user->id,
            'score'           => 15,
            'total_questions' => 20,
            'created_at'      => now(),
        ]);

        $from = now()->startOfDay();
        $to   = now()->endOfDay();

        $service = app(ReportingService::class);
        $report  = $service->buildPeriodReport($from, $to, $typeA);

        $this->assertEquals(1, $report['total_attempts']);
        $this->assertEquals(1, $report['active_students']);
    }

    public function test_report_service_without_license_type_is_backward_compatible(): void
    {
        $typeA = LicenseType::factory()->create();
        $typeB = LicenseType::factory()->create();

        $quizA = Quiz::factory()->create(['license_type_id' => $typeA->id, 'status' => 'confirmed']);
        $quizB = Quiz::factory()->create(['license_type_id' => $typeB->id, 'status' => 'confirmed']);

        $user = User::factory()->create(['role' => 'viewer']);

        QuizAttempt::factory()->create([
            'quiz_id'         => $quizA->id,
            'user_id'         => $user->id,
            'score'           => 10,
            'total_questions' => 20,
            'created_at'      => now(),
        ]);
        QuizAttempt::factory()->create([
            'quiz_id'         => $quizB->id,
            'user_id'         => $user->id,
            'score'           => 15,
            'total_questions' => 20,
            'created_at'      => now(),
        ]);

        $from = now()->startOfDay();
        $to   = now()->endOfDay();

        $service = app(ReportingService::class);
        $report  = $service->buildPeriodReport($from, $to);

        $this->assertEquals(2, $report['total_attempts']);
        $this->assertEquals(1, $report['active_students']);
    }

    public function test_pdf_report_header_includes_license_type_when_filtered(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $typeA = LicenseType::factory()->create(['code' => 'TIPO_A', 'name' => 'Patente A']);

        $quiz = Quiz::factory()->create(['license_type_id' => $typeA->id, 'status' => 'confirmed']);
        QuizAttempt::factory()->create(['quiz_id' => $quiz->id, 'score' => 10, 'total_questions' => 20]);

        $url = route('admin.reports.export-pdf') . '?' . http_build_query([
            'from'            => now()->format('Y-m-d'),
            'to'              => now()->format('Y-m-d'),
            'license_type_id' => $typeA->id,
        ]);
        $response = $this->actingAs($admin)->get($url);

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringContainsString('TIPO_A', $response->headers->get('Content-Disposition'));
    }

    public function test_editor_dashboard_filtered_by_license_type_shows_coherent_kpi(): void
    {
        $editor = User::factory()->create(['role' => 'editor']);
        $typeA  = LicenseType::factory()->create();

        $response = $this->actingAs($editor)
            ->get(route('editor.dashboard', ['license_type_id' => $typeA->id]));

        $response->assertStatus(200);
        $response->assertViewHas('selectedLicenseTypeId', $typeA->id);
    }

    public function test_generate_reports_by_license_command_runs_without_exception(): void
    {
        $typeA = LicenseType::factory()->create();
        Quiz::factory()->create(['license_type_id' => $typeA->id, 'status' => 'confirmed']);

        $this->artisan('reports:generate-by-license monthly --license-type=' . $typeA->code)
            ->assertExitCode(0);
    }
}
