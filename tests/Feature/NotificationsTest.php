<?php

namespace Tests\Feature;

use App\Models\Quiz;
use App\Models\QuizEnrollment;
use App\Models\User;
use App\Notifications\IscrizioneQuizApprovataNotification;
use App\Notifications\IscrizioneQuizRiapertaNotification;
use App\Notifications\IscrizioneQuizRifiutataNotification;
use App\Http\Livewire\NotificationBell;
use App\Notifications\AnagraficaModificataNotification;
use App\Notifications\NuovaIscrizioneQuizNotification;
use App\Notifications\NuovaRichiestaAnagraficaNotification;
use App\Notifications\QuizConfermatoNotification;
use App\Notifications\QuizEsameCompletatoNotification;
use App\Notifications\RegistrazioneApprovataNotification;
use App\Notifications\RegistrazioneRifiutataNotification;
use App\Notifications\RuoloAggiornatoNotification;
use Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class NotificationsTest extends TestCase
{
    use RefreshDatabase;

    /*
    |--------------------------------------------------------------------------
    | ISCRIZIONE ANAGRAFICA
    |--------------------------------------------------------------------------
    */

    public function test_viewer_submitting_registration_notifies_admins(): void
    {
        Notification::fake();
        Storage::fake('public');

        $admin1 = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $admin2 = User::factory()->create(['role' => User::ROLE_ADMIN]);
        User::factory()->create(['role' => User::ROLE_EDITOR]); // non deve essere notificato
        $viewer = User::factory()->create(['role' => User::ROLE_VIEWER]);

        $this->actingAs($viewer)
            ->post('/profile/registration', [
                'first_name'  => 'Mario',
                'last_name'   => 'Rossi',
                'address'     => 'Via Roma 1, Milano',
                'birth_date'  => '1995-06-15',
                'birth_place' => 'Milano',
                'fiscal_code' => 'RSSMRA95H15F205X',
                'id_document' => UploadedFile::fake()->create('id.pdf', 100, 'application/pdf'),
            ])
            ->assertRedirect('/profile');

        Notification::assertSentTo(
            [$admin1, $admin2],
            NuovaRichiestaAnagraficaNotification::class,
            fn ($notification) => $notification->viewer->is($viewer)
        );
        Notification::assertCount(2);
    }

    public function test_admin_approving_registration_notifies_viewer(): void
    {
        Notification::fake();

        $admin  = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $viewer = User::factory()->create([
            'role'                       => User::ROLE_VIEWER,
            'first_name'                 => 'Mario',
            'last_name'                  => 'Rossi',
            'registration_status'        => User::REG_PENDING,
            'registration_submitted_at'  => now(),
        ]);

        $this->actingAs($admin)
            ->post("/admin/registrations/{$viewer->id}/approve")
            ->assertRedirect(route('admin.registrations.index'));

        Notification::assertSentTo($viewer, RegistrazioneApprovataNotification::class);
    }

    public function test_admin_rejecting_registration_notifies_viewer_with_reason(): void
    {
        Notification::fake();

        $admin  = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $viewer = User::factory()->create([
            'role'                       => User::ROLE_VIEWER,
            'registration_status'        => User::REG_PENDING,
            'registration_submitted_at'  => now(),
        ]);

        $this->actingAs($admin)
            ->post("/admin/registrations/{$viewer->id}/reject", [
                'reason' => 'Documento illeggibile',
            ])
            ->assertRedirect(route('admin.registrations.index'));

        Notification::assertSentTo(
            $viewer,
            RegistrazioneRifiutataNotification::class,
            fn ($notification) => $notification->motivazione === 'Documento illeggibile'
        );
    }

    public function test_resubmitting_anagrafica_after_approval_notifies_admins_with_warning_class(): void
    {
        Notification::fake();
        Storage::fake('public');

        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $viewer = User::factory()->create([
            'role'                       => User::ROLE_VIEWER,
            'registration_status'        => User::REG_APPROVED,
            'registration_submitted_at'  => now()->subDay(),
            'registration_reviewed_at'   => now()->subDay(),
            'first_name'                 => 'Mario',
            'last_name'                  => 'Rossi',
        ]);

        $this->actingAs($viewer)
            ->post('/profile/registration', [
                'first_name'  => 'Mario',
                'last_name'   => 'Rossi',
                'address'     => 'Via Milano 99, Roma',
                'birth_date'  => '1990-01-01',
                'birth_place' => 'Roma',
                'fiscal_code' => 'RSSMRA90A01H501Z',
                'id_document' => UploadedFile::fake()->create('id.pdf', 100, 'application/pdf'),
            ])
            ->assertRedirect('/profile');

        Notification::assertSentTo(
            $admin,
            AnagraficaModificataNotification::class,
            fn ($notification) => $notification->viewer->is($viewer)
        );
        // Il primo invio (case 'none' → 'pending') passa per NuovaRichiestaAnagraficaNotification.
        // Qui invece eravamo in 'approved': deve essere AnagraficaModificataNotification.
        Notification::assertNotSentTo($admin, NuovaRichiestaAnagraficaNotification::class);
        $this->assertSame(User::REG_PENDING, $viewer->fresh()->registration_status);
    }

    /*
    |--------------------------------------------------------------------------
    | ISCRIZIONE QUIZ
    |--------------------------------------------------------------------------
    */

    public function test_viewer_requesting_quiz_enrollment_notifies_admins(): void
    {
        Notification::fake();

        $admin  = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $viewer = User::factory()->create([
            'role'                => User::ROLE_VIEWER,
            'registration_status' => User::REG_APPROVED,
        ]);
        $quiz = $this->makeConfirmedQuiz();

        $this->actingAs($viewer)
            ->post("/quiz/{$quiz->id}/enrollments")
            ->assertSessionHas('success');

        Notification::assertSentTo(
            $admin,
            NuovaIscrizioneQuizNotification::class,
            fn ($notification) => $notification->viewer->is($viewer) && $notification->quiz->is($quiz)
        );
    }

    public function test_admin_approving_quiz_enrollment_notifies_viewer(): void
    {
        Notification::fake();

        $admin  = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $viewer = User::factory()->create([
            'role'                => User::ROLE_VIEWER,
            'registration_status' => User::REG_APPROVED,
        ]);
        $quiz = $this->makeConfirmedQuiz();

        $enrollment = QuizEnrollment::create([
            'quiz_id' => $quiz->id,
            'user_id' => $viewer->id,
            'status'  => QuizEnrollment::STATUS_PENDING,
        ]);

        $this->actingAs($admin)
            ->post("/admin/enrollments/{$enrollment->id}/approve")
            ->assertSessionHas('success');

        Notification::assertSentTo(
            $viewer,
            IscrizioneQuizApprovataNotification::class,
            fn ($notification) => $notification->quiz->is($quiz)
        );
    }

    public function test_admin_rejecting_quiz_enrollment_notifies_viewer(): void
    {
        Notification::fake();

        $admin  = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $viewer = User::factory()->create([
            'role'                => User::ROLE_VIEWER,
            'registration_status' => User::REG_APPROVED,
        ]);
        $quiz = $this->makeConfirmedQuiz();

        $enrollment = QuizEnrollment::create([
            'quiz_id' => $quiz->id,
            'user_id' => $viewer->id,
            'status'  => QuizEnrollment::STATUS_PENDING,
        ]);

        $this->actingAs($admin)
            ->post("/admin/enrollments/{$enrollment->id}/reject")
            ->assertSessionHas('success');

        Notification::assertSentTo(
            $viewer,
            IscrizioneQuizRifiutataNotification::class,
            fn ($notification) => $notification->quiz->is($quiz)
        );
    }

    public function test_quiz_confirmation_notifies_eligible_viewers(): void
    {
        Notification::fake();

        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $approvedViewer = User::factory()->create([
            'role'                => User::ROLE_VIEWER,
            'registration_status' => User::REG_APPROVED,
        ]);
        $pendingViewer = User::factory()->create([
            'role'                => User::ROLE_VIEWER,
            'registration_status' => User::REG_PENDING,
        ]);
        $unsubmittedViewer = User::factory()->create([
            'role'                => User::ROLE_VIEWER,
            'registration_status' => User::REG_NONE,
        ]);
        $editor = User::factory()->create(['role' => User::ROLE_EDITOR]);

        $quiz = $this->makeConfirmedQuiz();
        // Riportiamolo a "pubblicato" e poi lo confermiamo via endpoint admin
        // così test_quiz_confirmation_* esercita davvero la transizione.
        $quiz->update([
            'status'       => Quiz::STATUS_PUBLISHED,
            'confirmed_at' => null,
            'confirmed_by' => null,
        ]);
        $quiz->questions()->attach(
            \App\Models\Question::factory()->create()->id
        );

        $this->actingAs($admin)
            ->post("/admin/quizzes/{$quiz->id}/confirm")
            ->assertSessionHas('success');

        Notification::assertSentTo(
            $approvedViewer,
            QuizConfermatoNotification::class,
            fn ($notification) => $notification->quiz->is($quiz->fresh())
        );
        Notification::assertNotSentTo($pendingViewer, QuizConfermatoNotification::class);
        Notification::assertNotSentTo($unsubmittedViewer, QuizConfermatoNotification::class);
        Notification::assertNotSentTo($editor, QuizConfermatoNotification::class);
        Notification::assertNotSentTo($admin, QuizConfermatoNotification::class);
    }

    public function test_admin_reopening_quiz_enrollment_notifies_viewer(): void
    {
        Notification::fake();

        $admin  = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $viewer = User::factory()->create([
            'role'                => User::ROLE_VIEWER,
            'registration_status' => User::REG_APPROVED,
        ]);
        $quiz = $this->makeConfirmedQuiz();

        QuizEnrollment::create([
            'quiz_id'      => $quiz->id,
            'user_id'      => $viewer->id,
            'status'       => QuizEnrollment::STATUS_COMPLETED,
            'completed_at' => now(),
        ]);

        $this->actingAs($admin)
            ->post("/admin/quizzes/{$quiz->id}/enrollments/reopen/{$viewer->id}")
            ->assertSessionHas('success');

        Notification::assertSentTo(
            $viewer,
            IscrizioneQuizRiapertaNotification::class,
            fn ($notification) => $notification->quiz->is($quiz)
        );
    }

    public function test_completing_official_quiz_notifies_admins_with_score(): void
    {
        Notification::fake();

        $admin  = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $viewer = User::factory()->create([
            'role'                => User::ROLE_VIEWER,
            'registration_status' => User::REG_APPROVED,
        ]);
        $quiz = $this->makeConfirmedQuiz();
        $question = \App\Models\Question::factory()->create(['is_true' => true]);
        $quiz->questions()->attach($question->id);

        $enrollment = QuizEnrollment::create([
            'quiz_id' => $quiz->id,
            'user_id' => $viewer->id,
            'status'  => QuizEnrollment::STATUS_APPROVED,
        ]);

        $this->actingAs($viewer)->post(route('quiz.attempts.store'), [
            'quiz_id'  => $quiz->id,
            'answers'  => [$question->id => '1'],
            'duration' => 120,
        ]);

        $this->assertSame(
            QuizEnrollment::STATUS_COMPLETED,
            $enrollment->fresh()->status
        );

        Notification::assertSentTo(
            $admin,
            QuizEsameCompletatoNotification::class,
            fn ($notification) => $notification->viewer->is($viewer)
                && $notification->quiz->is($quiz)
                && $notification->attempt->score === 1
                && $notification->attempt->total_questions === 1
        );
    }

    /*
    |--------------------------------------------------------------------------
    | RUOLO UTENTE
    |--------------------------------------------------------------------------
    */

    public function test_role_change_notifies_user(): void
    {
        Notification::fake();

        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $user  = User::factory()->create(['role' => User::ROLE_VIEWER]);

        $this->actingAs($admin)
            ->put("/admin/users/{$user->id}", [
                'name'  => $user->name,
                'email' => $user->email,
                'role'  => User::ROLE_EDITOR,
            ]);

        $this->assertSame(User::ROLE_EDITOR, $user->fresh()->role);

        Notification::assertSentTo(
            $user,
            RuoloAggiornatoNotification::class,
            fn ($notification) => $notification->oldRole === User::ROLE_VIEWER
                && $notification->newRole === User::ROLE_EDITOR
        );
    }

    public function test_user_update_without_role_change_does_not_notify(): void
    {
        Notification::fake();

        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $user  = User::factory()->create([
            'role'  => User::ROLE_VIEWER,
            'name'  => 'Vecchio Nome',
        ]);

        $this->actingAs($admin)
            ->put("/admin/users/{$user->id}", [
                'name'  => 'Nuovo Nome',
                'email' => $user->email,
                'role'  => User::ROLE_VIEWER,
            ]);

        $this->assertSame('Nuovo Nome', $user->fresh()->name);

        Notification::assertNotSentTo($user, RuoloAggiornatoNotification::class);
    }

    /*
    |--------------------------------------------------------------------------
    | FALLBACK — il workflow continua se il mail driver fallisce
    |--------------------------------------------------------------------------
    */

    public function test_registration_approval_redirects_even_if_notification_dispatch_fails(): void
    {
        Notification::shouldReceive('send')
            ->andThrow(new Exception('SMTP down'));
        // Necessario per gli admin che hanno la trait Notifiable
        Notification::shouldReceive('sendNow')->andReturn(null);

        $admin  = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $viewer = User::factory()->create([
            'role'                       => User::ROLE_VIEWER,
            'registration_status'        => User::REG_PENDING,
            'registration_submitted_at'  => now(),
        ]);

        $this->actingAs($admin)
            ->post("/admin/registrations/{$viewer->id}/approve")
            ->assertRedirect(route('admin.registrations.index'))
            ->assertSessionHas('success');

        $this->assertSame(User::REG_APPROVED, $viewer->fresh()->registration_status);
    }

    public function test_quiz_enrollment_approval_redirects_even_if_notification_dispatch_fails(): void
    {
        Notification::shouldReceive('send')
            ->andThrow(new Exception('SMTP down'));
        Notification::shouldReceive('sendNow')->andReturn(null);

        $admin  = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $viewer = User::factory()->create([
            'role'                => User::ROLE_VIEWER,
            'registration_status' => User::REG_APPROVED,
        ]);
        $quiz = $this->makeConfirmedQuiz();

        $enrollment = QuizEnrollment::create([
            'quiz_id' => $quiz->id,
            'user_id' => $viewer->id,
            'status'  => QuizEnrollment::STATUS_PENDING,
        ]);

        $this->actingAs($admin)
            ->post("/admin/enrollments/{$enrollment->id}/approve")
            ->assertSessionHas('success');

        $this->assertSame(QuizEnrollment::STATUS_APPROVED, $enrollment->fresh()->status);
    }

    private function makeConfirmedQuiz(): Quiz
    {
        return Quiz::create([
            'title'         => 'Esame ufficiale',
            'time_limit'    => 1800,
            'max_errors'    => 4,
            'max_questions' => 40,
            'status'        => Quiz::STATUS_CONFIRMED,
            'published_at'  => now(),
            'confirmed_at'  => now(),
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | NOTIFICHE IN-APP — canale database, pagina lista, eliminazione, bell
    |--------------------------------------------------------------------------
    */

    public function test_database_channel_writes_expected_payload(): void
    {
        $viewer = User::factory()->create([
            'role'                => User::ROLE_VIEWER,
            'registration_status' => User::REG_APPROVED,
        ]);

        $viewer->notify(new RegistrazioneApprovataNotification());

        $this->assertDatabaseCount('notifications', 1);

        $row = DatabaseNotification::query()->first();

        $this->assertSame(RegistrazioneApprovataNotification::class, $row->type);
        $this->assertSame((string) $viewer->id, (string) $row->notifiable_id);
        $this->assertSame($viewer->getMorphClass(), $row->notifiable_type);
        $this->assertNull($row->read_at);

        $this->assertSame('Iscrizione approvata', $row->data['title']);
        $this->assertArrayHasKey('body', $row->data);
        $this->assertArrayHasKey('url', $row->data);
        $this->assertSame('fas fa-check-circle', $row->data['icon']);
        $this->assertSame('success', $row->data['color']);
    }

    public function test_notifications_index_marks_unread_as_read_and_renders_them(): void
    {
        $viewer = User::factory()->create(['role' => User::ROLE_VIEWER]);

        $viewer->notify(new RegistrazioneApprovataNotification());
        $viewer->notify(new RegistrazioneRifiutataNotification('Doc mancante'));

        $this->assertSame(2, $viewer->unreadNotifications()->count());

        $response = $this->actingAs($viewer)->get(route('notifications.index'));

        $response->assertOk();
        $response->assertSee('Iscrizione approvata');
        $response->assertSee('Iscrizione rifiutata');

        $this->assertSame(0, $viewer->fresh()->unreadNotifications()->count());
        $this->assertSame(2, $viewer->fresh()->notifications()->count());
    }

    public function test_destroy_returns_403_when_deleting_another_users_notification(): void
    {
        $owner    = User::factory()->create(['role' => User::ROLE_VIEWER]);
        $attacker = User::factory()->create(['role' => User::ROLE_VIEWER]);

        $owner->notify(new RegistrazioneApprovataNotification());
        $notification = $owner->notifications()->first();

        $this->actingAs($attacker)
            ->delete(route('notifications.destroy', $notification->id))
            ->assertForbidden();

        $this->assertDatabaseHas('notifications', ['id' => $notification->id]);
    }

    public function test_destroy_all_only_removes_authenticated_user_notifications(): void
    {
        $viewer = User::factory()->create(['role' => User::ROLE_VIEWER]);
        $other  = User::factory()->create(['role' => User::ROLE_VIEWER]);

        $viewer->notify(new RegistrazioneApprovataNotification());
        $viewer->notify(new RegistrazioneRifiutataNotification('Doc mancante'));
        $other->notify(new RegistrazioneApprovataNotification());

        $this->actingAs($viewer)
            ->delete(route('notifications.destroyAll'))
            ->assertRedirect();

        $this->assertSame(0, $viewer->fresh()->notifications()->count());
        $this->assertSame(1, $other->fresh()->notifications()->count());
    }

    public function test_notification_bell_livewire_returns_correct_unread_count(): void
    {
        $viewer = User::factory()->create(['role' => User::ROLE_VIEWER]);

        $viewer->notify(new RegistrazioneApprovataNotification());
        $viewer->notify(new RegistrazioneRifiutataNotification('Doc mancante'));

        $this->actingAs($viewer);

        Livewire::test(NotificationBell::class)
            ->assertSet('unreadCount', 2)
            ->assertSee('Iscrizione approvata')
            ->call('markAllAsRead')
            ->assertSet('unreadCount', 0);

        $this->assertSame(0, $viewer->fresh()->unreadNotifications()->count());
    }
}
