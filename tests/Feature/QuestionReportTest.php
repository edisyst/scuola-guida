<?php

namespace Tests\Feature;

use App\Http\Livewire\ReportButton;
use App\Models\Question;
use App\Models\QuestionReport;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class QuestionReportTest extends TestCase
{
    use RefreshDatabase;

    private function viewer(): User
    {
        return User::factory()->create(['role' => User::ROLE_VIEWER]);
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => User::ROLE_ADMIN]);
    }

    private function editor(): User
    {
        // Editor con permesso edit_question (di default in tutti i ruoli i
        // permessi sono configurati dal DB; lo settiamo via permissions override).
        return User::factory()->create([
            'role'        => User::ROLE_EDITOR,
            'permissions' => ['edit_question'],
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | LIVEWIRE — ReportButton (invio segnalazioni dal viewer)
    |--------------------------------------------------------------------------
    */

    public function test_viewer_can_send_valid_report_via_livewire(): void
    {
        $viewer   = $this->viewer();
        $question = Question::factory()->create();

        $this->actingAs($viewer);

        Livewire::test(ReportButton::class, ['questionId' => $question->id])
            ->set('type', 'risposta_errata')
            ->set('body', 'La risposta segnata come corretta è in contraddizione con il CdS.')
            ->call('sendReport')
            ->assertHasNoErrors()
            ->assertSet('submitted', true)
            ->assertSet('open', false);

        $this->assertDatabaseHas('question_reports', [
            'question_id' => $question->id,
            'user_id'     => $viewer->id,
            'type'        => 'risposta_errata',
            'status'      => 'pending',
        ]);
    }

    public function test_body_too_short_fails_validation(): void
    {
        $viewer   = $this->viewer();
        $question = Question::factory()->create();

        $this->actingAs($viewer);

        Livewire::test(ReportButton::class, ['questionId' => $question->id])
            ->set('type', 'altro')
            ->set('body', 'corto')
            ->call('sendReport')
            ->assertHasErrors(['body' => 'min']);

        $this->assertDatabaseCount('question_reports', 0);
    }

    public function test_invalid_type_fails_validation(): void
    {
        $viewer   = $this->viewer();
        $question = Question::factory()->create();

        $this->actingAs($viewer);

        Livewire::test(ReportButton::class, ['questionId' => $question->id])
            ->set('type', 'tipo_inesistente')
            ->set('body', 'Una segnalazione abbastanza lunga per superare la validazione.')
            ->call('sendReport')
            ->assertHasErrors(['type']);

        $this->assertDatabaseCount('question_reports', 0);
    }

    public function test_anti_spam_limits_pending_reports_to_three_per_question(): void
    {
        $viewer   = $this->viewer();
        $question = Question::factory()->create();

        // Pre-popola 3 segnalazioni pending già esistenti.
        QuestionReport::factory()->count(3)->create([
            'question_id' => $question->id,
            'user_id'     => $viewer->id,
            'status'      => 'pending',
        ]);

        $this->actingAs($viewer);

        Livewire::test(ReportButton::class, ['questionId' => $question->id])
            ->set('type', 'altro')
            ->set('body', 'Ancora un\'altra segnalazione per la stessa domanda, dovrebbe essere bloccata.')
            ->call('sendReport')
            ->assertHasErrors(['body']);

        $this->assertSame(3, QuestionReport::where('user_id', $viewer->id)
            ->where('question_id', $question->id)
            ->count());
    }

    /*
    |--------------------------------------------------------------------------
    | ADMIN — index, show, accept, reject, destroy
    |--------------------------------------------------------------------------
    */

    public function test_admin_can_access_reports_index(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->get(route('admin.question-reports.index'))
            ->assertOk();
    }

    public function test_viewer_cannot_access_reports_index(): void
    {
        $viewer = $this->viewer();

        $this->actingAs($viewer)
            ->get(route('admin.question-reports.index'))
            ->assertForbidden();
    }

    public function test_admin_accept_marks_report_as_accepted(): void
    {
        $admin  = $this->admin();
        $report = QuestionReport::factory()->create(['status' => 'pending']);

        $this->actingAs($admin)
            ->patch(route('admin.question-reports.accept', $report), [
                'admin_note' => 'Hai ragione, correggeremo la domanda.',
            ])
            ->assertRedirect(route('admin.question-reports.index'))
            ->assertSessionHas('success');

        $fresh = $report->fresh();
        $this->assertSame('accepted', $fresh->status);
        $this->assertSame($admin->id, $fresh->resolved_by);
        $this->assertNotNull($fresh->resolved_at);
        $this->assertSame('Hai ragione, correggeremo la domanda.', $fresh->admin_note);
    }

    public function test_admin_reject_marks_report_as_rejected(): void
    {
        $admin  = $this->admin();
        $report = QuestionReport::factory()->create(['status' => 'pending']);

        $this->actingAs($admin)
            ->patch(route('admin.question-reports.reject', $report))
            ->assertRedirect(route('admin.question-reports.index'));

        $fresh = $report->fresh();
        $this->assertSame('rejected', $fresh->status);
        $this->assertSame($admin->id, $fresh->resolved_by);
        $this->assertNotNull($fresh->resolved_at);
    }

    public function test_admin_destroy_removes_report(): void
    {
        $admin  = $this->admin();
        $report = QuestionReport::factory()->create();

        $this->actingAs($admin)
            ->delete(route('admin.question-reports.destroy', $report))
            ->assertRedirect();

        $this->assertDatabaseMissing('question_reports', ['id' => $report->id]);
    }

    public function test_index_stats_returns_correct_kpi_counts(): void
    {
        $admin = $this->admin();

        QuestionReport::factory()->count(2)->create(['status' => 'pending']);
        QuestionReport::factory()->count(3)->create(['status' => 'accepted']);
        QuestionReport::factory()->count(1)->create(['status' => 'rejected']);

        $this->actingAs($admin)
            ->get(route('admin.question-reports.index'))
            ->assertOk()
            ->assertViewHas('stats', fn ($stats) =>
                $stats['pending']  === 2
                && $stats['accepted'] === 3
                && $stats['rejected'] === 1
            );
    }

    public function test_deleting_question_cascades_to_reports(): void
    {
        $question = Question::factory()->create();
        $report   = QuestionReport::factory()->create(['question_id' => $question->id]);

        $question->delete();

        $this->assertDatabaseMissing('question_reports', ['id' => $report->id]);
    }

    public function test_show_view_hides_action_form_for_already_resolved_report(): void
    {
        $admin  = $this->admin();
        $report = QuestionReport::factory()->create([
            'status'      => 'accepted',
            'resolved_by' => $admin->id,
            'resolved_at' => now(),
            'admin_note'  => 'Già gestita',
        ]);

        $response = $this->actingAs($admin)
            ->get(route('admin.question-reports.show', $report))
            ->assertOk();

        // Il form di gestione (azioni accept/reject) non deve comparire.
        $response->assertDontSee('Gestisci segnalazione');
        // Ma la nota della risoluzione sì.
        $response->assertSee('Già gestita');
    }

    public function test_editor_with_edit_question_permission_can_moderate(): void
    {
        $editor = $this->editor();
        $report = QuestionReport::factory()->create(['status' => 'pending']);

        $this->actingAs($editor)
            ->get(route('admin.question-reports.index'))
            ->assertOk();

        $this->actingAs($editor)
            ->patch(route('admin.question-reports.accept', $report))
            ->assertRedirect();

        $this->assertSame('accepted', $report->fresh()->status);
    }
}
