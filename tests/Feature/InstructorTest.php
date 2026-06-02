<?php

namespace Tests\Feature;

use App\Models\InstructorNote;
use App\Models\Quiz;
use App\Models\User;
use App\Notifications\InstructorStudentOutcome;
use App\Services\InstructorService;
use App\Services\QuizAttemptService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class InstructorTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(\App\Http\Middleware\EnsureTwoFactorAuthenticated::class);
    }

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

    // ─── isInstructor() ───────────────────────────────────────────────────────

    public function test_isInstructor_returns_true_for_instructor_role(): void
    {
        $instructor = $this->makeInstructor();
        $this->assertTrue($instructor->isInstructor());
        $this->assertFalse($instructor->isAdmin());
        $this->assertFalse($instructor->isEditor());
        $this->assertFalse($instructor->isViewer());
    }

    // ─── canEditXxx() devono ritornare false per instructor ───────────────────

    public function test_canEdit_methods_return_false_for_instructor(): void
    {
        $instructor = $this->makeInstructor();

        $this->assertFalse($instructor->canEditQuestion());
        $this->assertFalse($instructor->canEditQuiz());
        $this->assertFalse($instructor->canEditCategory());
        $this->assertFalse($instructor->canEditUser());
        $this->assertFalse($instructor->canCreateQuestion());
        $this->assertFalse($instructor->canDeleteQuestion());
    }

    // ─── InstructorService::assignStudent ────────────────────────────────────

    public function test_assign_student_creates_pivot_record(): void
    {
        $admin      = $this->makeAdmin();
        $instructor = $this->makeInstructor();
        $student    = $this->makeViewer();

        $service = app(InstructorService::class);
        $service->assignStudent($instructor, $student, $admin);

        $this->assertDatabaseHas('instructor_student', [
            'instructor_id' => $instructor->id,
            'student_id'    => $student->id,
        ]);
    }

    public function test_assign_student_is_idempotent(): void
    {
        $admin      = $this->makeAdmin();
        $instructor = $this->makeInstructor();
        $student    = $this->makeViewer();

        $service = app(InstructorService::class);
        $service->assignStudent($instructor, $student, $admin);
        $service->assignStudent($instructor, $student, $admin); // secondo tentativo

        $this->assertSame(
            1,
            DB::table('instructor_student')
                ->where('instructor_id', $instructor->id)
                ->where('student_id', $student->id)
                ->count()
        );
    }

    public function test_assign_fails_if_instructor_is_not_instructor_role(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $service = app(InstructorService::class);
        $viewer  = $this->makeViewer();
        $student = $this->makeViewer();
        $admin   = $this->makeAdmin();

        $service->assignStudent($viewer, $student, $admin);
    }

    public function test_assign_fails_if_student_is_not_viewer(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $service    = app(InstructorService::class);
        $instructor = $this->makeInstructor();
        $editor     = $this->makeEditor();
        $admin      = $this->makeAdmin();

        $service->assignStudent($instructor, $editor, $admin);
    }

    // ─── hasStudent() ────────────────────────────────────────────────────────

    public function test_hasStudent_returns_true_after_assignment(): void
    {
        $admin      = $this->makeAdmin();
        $instructor = $this->makeInstructor();
        $student    = $this->makeViewer();

        $service = app(InstructorService::class);
        $service->assignStudent($instructor, $student, $admin);

        $instructor->refresh();
        $this->assertTrue($instructor->hasStudent($student));
    }

    public function test_hasStudent_returns_false_for_unassigned_student(): void
    {
        $instructor = $this->makeInstructor();
        $student    = $this->makeViewer();

        $this->assertFalse($instructor->hasStudent($student));
    }

    // ─── Accesso route instructor ─────────────────────────────────────────────

    public function test_instructor_can_access_students_index(): void
    {
        $instructor = $this->makeInstructor();

        $response = $this->actingAs($instructor)->get(route('instructor.students.index'));

        $response->assertOk();
    }

    public function test_viewer_cannot_access_instructor_area(): void
    {
        $viewer = $this->makeViewer();

        $this->actingAs($viewer)->get(route('instructor.students.index'))->assertForbidden();
    }

    public function test_editor_cannot_access_instructor_area(): void
    {
        $editor = $this->makeEditor();

        $this->actingAs($editor)->get(route('instructor.students.index'))->assertForbidden();
    }

    public function test_admin_can_access_instructor_area(): void
    {
        $admin = $this->makeAdmin();

        $this->actingAs($admin)->get(route('instructor.students.index'))->assertOk();
    }

    // ─── showStudent — autorizzazione ────────────────────────────────────────

    public function test_instructor_can_see_assigned_student(): void
    {
        $admin      = $this->makeAdmin();
        $instructor = $this->makeInstructor();
        $student    = $this->makeViewer();

        app(InstructorService::class)->assignStudent($instructor, $student, $admin);

        $this->actingAs($instructor)
             ->get(route('instructor.students.show', $student))
             ->assertOk();
    }

    public function test_instructor_cannot_see_unassigned_student(): void
    {
        $instructor = $this->makeInstructor();
        $student    = $this->makeViewer();

        $this->actingAs($instructor)
             ->get(route('instructor.students.show', $student))
             ->assertForbidden();
    }

    public function test_admin_can_see_any_student(): void
    {
        $admin   = $this->makeAdmin();
        $student = $this->makeViewer();

        $this->actingAs($admin)
             ->get(route('instructor.students.show', $student))
             ->assertOk();
    }

    // ─── cascadeOnDelete ─────────────────────────────────────────────────────

    public function test_deleting_instructor_removes_pivot_rows(): void
    {
        $admin      = $this->makeAdmin();
        $instructor = $this->makeInstructor();
        $student    = $this->makeViewer();

        app(InstructorService::class)->assignStudent($instructor, $student, $admin);

        $this->assertDatabaseHas('instructor_student', ['instructor_id' => $instructor->id]);

        $instructor->delete();

        $this->assertDatabaseMissing('instructor_student', ['instructor_id' => $instructor->id]);
    }

    public function test_deleting_student_removes_pivot_rows(): void
    {
        $admin      = $this->makeAdmin();
        $instructor = $this->makeInstructor();
        $student    = $this->makeViewer();

        app(InstructorService::class)->assignStudent($instructor, $student, $admin);

        $this->assertDatabaseHas('instructor_student', ['student_id' => $student->id]);

        $student->delete();

        $this->assertDatabaseMissing('instructor_student', ['student_id' => $student->id]);
    }

    // ─── Admin route assegnazioni ────────────────────────────────────────────

    public function test_admin_can_access_instructors_management_index(): void
    {
        $admin = $this->makeAdmin();

        $this->actingAs($admin)->get(route('admin.instructors.index'))->assertOk();
    }

    public function test_viewer_cannot_access_instructors_management(): void
    {
        $viewer = $this->makeViewer();

        // viewer non ha il middleware role:admin, risponde 403
        $this->actingAs($viewer)->get(route('admin.instructors.index'))->assertForbidden();
    }

    public function test_admin_can_assign_student_via_http(): void
    {
        $admin      = $this->makeAdmin();
        $instructor = $this->makeInstructor();
        $student    = $this->makeViewer();

        $this->actingAs($admin)
             ->post(route('admin.instructors.assign', $instructor), [
                 'student_ids' => [$student->id],
             ])
             ->assertRedirect(route('admin.instructors.edit', $instructor));

        $this->assertDatabaseHas('instructor_student', [
            'instructor_id' => $instructor->id,
            'student_id'    => $student->id,
        ]);
    }

    public function test_admin_can_unassign_student_via_http(): void
    {
        $admin      = $this->makeAdmin();
        $instructor = $this->makeInstructor();
        $student    = $this->makeViewer();

        app(InstructorService::class)->assignStudent($instructor, $student, $admin);

        $this->actingAs($admin)
             ->delete(route('admin.instructors.unassign', [$instructor, $student]))
             ->assertRedirect(route('admin.instructors.edit', $instructor));

        $this->assertDatabaseMissing('instructor_student', [
            'instructor_id' => $instructor->id,
            'student_id'    => $student->id,
        ]);
    }

    // ─── getStudentProgress ──────────────────────────────────────────────────

    public function test_getStudentProgress_returns_expected_keys(): void
    {
        $student = $this->makeViewer();

        $progress = app(InstructorService::class)->getStudentProgress($student);

        $this->assertArrayHasKey('student', $progress);
        $this->assertArrayHasKey('stats', $progress);
        $this->assertArrayHasKey('streak', $progress);
        $this->assertArrayHasKey('badges', $progress);
        $this->assertSame($student->id, $progress['student']['id']);
    }

    // ─── Note istruttore ─────────────────────────────────────────────────────

    public function test_instructor_can_add_note_on_assigned_student(): void
    {
        $admin      = $this->makeAdmin();
        $instructor = $this->makeInstructor();
        $student    = $this->makeViewer();

        app(InstructorService::class)->assignStudent($instructor, $student, $admin);

        $this->actingAs($instructor)
             ->post(route('instructor.students.notes.store', $student), ['body' => 'Studente in miglioramento.'])
             ->assertRedirect(route('instructor.students.show', $student));

        $this->assertDatabaseHas('instructor_notes', [
            'instructor_id' => $instructor->id,
            'student_id'    => $student->id,
            'body'          => 'Studente in miglioramento.',
        ]);
    }

    public function test_instructor_cannot_add_note_on_unassigned_student(): void
    {
        $instructor = $this->makeInstructor();
        $student    = $this->makeViewer();

        $this->actingAs($instructor)
             ->post(route('instructor.students.notes.store', $student), ['body' => 'Nota.'])
             ->assertForbidden();
    }

    public function test_instructor_cannot_delete_note_of_another_instructor(): void
    {
        $admin       = $this->makeAdmin();
        $instructor1 = $this->makeInstructor();
        $instructor2 = $this->makeInstructor();
        $student     = $this->makeViewer();

        app(InstructorService::class)->assignStudent($instructor1, $student, $admin);
        app(InstructorService::class)->assignStudent($instructor2, $student, $admin);

        $note = InstructorNote::create([
            'instructor_id' => $instructor1->id,
            'student_id'    => $student->id,
            'body'          => 'Nota di istruttore 1.',
            'created_by'    => $instructor1->id,
        ]);

        $this->actingAs($instructor2)
             ->delete(route('instructor.students.notes.destroy', [$student, $note]))
             ->assertForbidden();

        $this->assertDatabaseHas('instructor_notes', ['id' => $note->id]);
    }

    public function test_cascade_delete_removes_notes_when_user_is_deleted(): void
    {
        $admin      = $this->makeAdmin();
        $instructor = $this->makeInstructor();
        $student    = $this->makeViewer();

        app(InstructorService::class)->assignStudent($instructor, $student, $admin);

        InstructorNote::create([
            'instructor_id' => $instructor->id,
            'student_id'    => $student->id,
            'body'          => 'Nota GDPR test.',
            'created_by'    => $instructor->id,
        ]);

        $this->assertDatabaseHas('instructor_notes', ['instructor_id' => $instructor->id]);

        $instructor->delete();

        $this->assertDatabaseMissing('instructor_notes', ['instructor_id' => $instructor->id]);
    }

    public function test_cascade_delete_removes_notes_when_student_is_deleted(): void
    {
        $admin      = $this->makeAdmin();
        $instructor = $this->makeInstructor();
        $student    = $this->makeViewer();

        app(InstructorService::class)->assignStudent($instructor, $student, $admin);

        InstructorNote::create([
            'instructor_id' => $instructor->id,
            'student_id'    => $student->id,
            'body'          => 'Nota GDPR test studente.',
            'created_by'    => $instructor->id,
        ]);

        $this->assertDatabaseHas('instructor_notes', ['student_id' => $student->id]);

        $student->delete();

        $this->assertDatabaseMissing('instructor_notes', ['student_id' => $student->id]);
    }

    public function test_export_pdf_returns_pdf_response(): void
    {
        $admin      = $this->makeAdmin();
        $instructor = $this->makeInstructor();
        $student    = $this->makeViewer();

        app(InstructorService::class)->assignStudent($instructor, $student, $admin);

        $response = $this->actingAs($instructor)
                         ->get(route('instructor.students.export-pdf', $student));

        $response->assertOk();
        $this->assertStringContainsString('application/pdf', $response->headers->get('Content-Type'));
    }

    public function test_notification_sent_to_instructor_on_student_quiz_completion(): void
    {
        Notification::fake();

        $admin      = $this->makeAdmin();
        $instructor = $this->makeInstructor();
        $student    = $this->makeViewer();

        app(InstructorService::class)->assignStudent($instructor, $student, $admin);

        $quiz = Quiz::factory()->create();
        $quiz->questions()->attach(
            \App\Models\Question::factory()->count(3)->create()->pluck('id'),
        );

        app(QuizAttemptService::class)->record(
            $student->id,
            $quiz->id,
            $quiz->questions->mapWithKeys(fn ($q) => [$q->id => ['correct' => 0]])->toArray(),
            null,
        );

        Notification::assertSentTo($instructor, InstructorStudentOutcome::class);
    }

    public function test_no_notification_when_student_has_no_instructor(): void
    {
        Notification::fake();

        $student = $this->makeViewer();

        $quiz = Quiz::factory()->create();
        $quiz->questions()->attach(
            \App\Models\Question::factory()->count(3)->create()->pluck('id'),
        );

        app(QuizAttemptService::class)->record(
            $student->id,
            $quiz->id,
            $quiz->questions->mapWithKeys(fn ($q) => [$q->id => ['correct' => 0]])->toArray(),
            null,
        );

        Notification::assertNotSentTo($student, InstructorStudentOutcome::class);
    }
}
