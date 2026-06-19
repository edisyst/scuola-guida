<?php

namespace Tests\Feature;

use App\Http\Middleware\EnsureTwoFactorAuthenticated;
use App\Models\Question;
use App\Models\QuestionReport;
use App\Models\Quiz;
use App\Models\User;
use Database\Seeders\SystemSettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RedesignComponentsTest extends TestCase
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
        return User::factory()->create(['role' => User::ROLE_ADMIN]);
    }

    /** Dashboard admin risponde 200 e usa sg-stat-icon semantici (non grad-* saturi). */
    public function test_admin_dashboard_responds_200_and_uses_semantic_stat_icons(): void
    {
        $response = $this->actingAs($this->admin())
            ->get(route('dashboard'))
            ->assertOk();

        $response->assertSee('sg-stat-icon--accent', false);
        $response->assertSee('sg-stat-icon--success', false);
        $response->assertDontSee('grad-blue', false);
        $response->assertDontSee('grad-green', false);
        $response->assertDontSee('small-box', false);
    }

    /** Pagina segnalazioni risponde 200 e usa sg-status-box (non small-box AdminLTE). */
    public function test_question_reports_index_responds_200_and_uses_status_box(): void
    {
        $response = $this->actingAs($this->admin())
            ->get(route('admin.question-reports.index'))
            ->assertOk();

        $response->assertSee('sg-status-box', false);
        $response->assertDontSee('small-box', false);
        $response->assertDontSee('bg-warning', false);
        $response->assertDontSee('bg-success', false);
    }

    /** Pagina segnalazioni usa sg-badge per gli stati (non badge Bootstrap). */
    public function test_question_reports_index_uses_sg_badge_for_states(): void
    {
        $question = Question::factory()->create();
        QuestionReport::factory()->create([
            'question_id' => $question->id,
            'status'      => 'pending',
        ]);

        $response = $this->actingAs($this->admin())
            ->get(route('admin.question-reports.index'))
            ->assertOk();

        $response->assertSee('sg-badge--pending', false);
        $response->assertDontSee('badge-warning', false);
    }

    /** Pagina segnalazioni non usa table-warning sulle righe (sfondo saturo rimosso). */
    public function test_question_reports_index_has_no_table_warning_row_class(): void
    {
        $question = Question::factory()->create();
        QuestionReport::factory()->create([
            'question_id' => $question->id,
            'status'      => 'pending',
        ]);

        $response = $this->actingAs($this->admin())
            ->get(route('admin.question-reports.index'))
            ->assertOk();

        $response->assertDontSee('table-warning', false);
    }

    /** Pagina gestione quiz risponde 200 e usa sg-badge per lo stato. */
    public function test_quizzes_index_responds_200_and_uses_sg_badge(): void
    {
        Quiz::factory()->create(['status' => 'draft']);

        $response = $this->actingAs($this->admin())
            ->get(route('admin.quizzes.index'))
            ->assertOk();

        $response->assertSee('sg-badge', false);
        $response->assertDontSee('badge-secondary', false);
    }

    /** Pagina gestione utenti risponde 200 e usa sg-badge (non badge Bootstrap) per tipo patente. */
    public function test_users_index_responds_200_and_uses_sg_badge(): void
    {
        $response = $this->actingAs($this->admin())
            ->get(route('admin.users.index'))
            ->assertOk();

        $response->assertSee('sg-badge', false);
        $response->assertDontSee('badge-secondary', false);
    }
}
