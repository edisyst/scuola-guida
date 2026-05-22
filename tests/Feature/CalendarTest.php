<?php

namespace Tests\Feature;

use App\Models\Quiz;
use App\Models\QuizEnrollment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CalendarTest extends TestCase
{
    use RefreshDatabase;

    // ── Helpers ──────────────────────────────────────────────────────────────

    private function viewer(string $regStatus = User::REG_APPROVED): User
    {
        return User::factory()->create([
            'role'                => User::ROLE_VIEWER,
            'registration_status' => $regStatus,
        ]);
    }

    private function confirmedQuiz(array $attrs = []): Quiz
    {
        return Quiz::factory()->create(array_merge([
            'status'       => Quiz::STATUS_CONFIRMED,
            'confirmed_at' => now(),
        ], $attrs));
    }

    // ── Tests: accesso ────────────────────────────────────────────────────────

    public function test_calendar_returns_200_for_authenticated_viewer(): void
    {
        $this->actingAs($this->viewer())
            ->get(route('calendar.index'))
            ->assertOk();
    }

    public function test_calendar_redirects_unauthenticated_user_to_login(): void
    {
        $this->get(route('calendar.index'))
            ->assertRedirect(route('login'));
    }

    // ── Tests: quiz nelle sezioni corrette ────────────────────────────────────

    public function test_upcoming_quiz_appears_in_upcoming_section(): void
    {
        $quiz = $this->confirmedQuiz([
            'enrollments_open_at'  => now()->addDays(5),
            'enrollments_close_at' => now()->addDays(10),
        ]);

        $response = $this->actingAs($this->viewer())
            ->get(route('calendar.index'));

        $response->assertOk()
            ->assertViewHas('upcoming', fn ($upcoming) => $upcoming->contains('id', $quiz->id))
            ->assertViewHas('open',     fn ($open)     => !$open->contains('id', $quiz->id))
            ->assertViewHas('closed',   fn ($closed)   => !$closed->contains('id', $quiz->id));
    }

    public function test_open_quiz_with_past_open_and_future_close_appears_in_open_section(): void
    {
        $quiz = $this->confirmedQuiz([
            'enrollments_open_at'  => now()->subDay(),
            'enrollments_close_at' => now()->addDays(5),
        ]);

        $response = $this->actingAs($this->viewer())
            ->get(route('calendar.index'));

        $response->assertOk()
            ->assertViewHas('open',     fn ($open)     => $open->contains('id', $quiz->id))
            ->assertViewHas('upcoming', fn ($upcoming) => !$upcoming->contains('id', $quiz->id))
            ->assertViewHas('closed',   fn ($closed)   => !$closed->contains('id', $quiz->id));
    }

    public function test_closed_quiz_appears_in_closed_section(): void
    {
        $quiz = $this->confirmedQuiz([
            'enrollments_open_at'  => now()->subDays(10),
            'enrollments_close_at' => now()->subDay(),
        ]);

        $response = $this->actingAs($this->viewer())
            ->get(route('calendar.index'));

        $response->assertOk()
            ->assertViewHas('closed',   fn ($closed)   => $closed->contains('id', $quiz->id))
            ->assertViewHas('upcoming', fn ($upcoming) => !$upcoming->contains('id', $quiz->id))
            ->assertViewHas('open',     fn ($open)     => !$open->contains('id', $quiz->id));
    }

    public function test_quiz_without_dates_appears_in_open_section(): void
    {
        $quiz = $this->confirmedQuiz([
            'enrollments_open_at'  => null,
            'enrollments_close_at' => null,
        ]);

        $response = $this->actingAs($this->viewer())
            ->get(route('calendar.index'));

        $response->assertOk()
            ->assertViewHas('open', fn ($open) => $open->contains('id', $quiz->id));
    }

    // ── Tests: badge "Già iscritto" ───────────────────────────────────────────

    public function test_enrolled_quiz_badge_appears_for_viewer(): void
    {
        $viewer = $this->viewer();
        $quiz   = $this->confirmedQuiz(['enrollments_open_at' => null, 'enrollments_close_at' => null]);

        QuizEnrollment::create([
            'user_id' => $viewer->id,
            'quiz_id' => $quiz->id,
            'status'  => QuizEnrollment::STATUS_PENDING,
        ]);

        $this->actingAs($viewer)
            ->get(route('calendar.index'))
            ->assertOk()
            ->assertViewHas('open', fn ($open) =>
                $open->where('id', $quiz->id)->first()?->enrollments->isNotEmpty()
            );
    }

    // ── Tests: pulsante iscrizione ────────────────────────────────────────────

    public function test_enrollment_button_not_shown_for_upcoming_quiz(): void
    {
        $this->confirmedQuiz([
            'title'               => 'Quiz Futuro Test',
            'enrollments_open_at' => now()->addDays(3),
        ]);

        $viewer = $this->viewer();

        // Il pulsante "Richiedi iscrizione" non deve apparire per quiz upcoming
        $this->actingAs($viewer)
            ->get(route('calendar.index'))
            ->assertOk()
            ->assertSee('Disponibile a breve');
    }

    public function test_enrollment_button_not_shown_for_closed_quiz(): void
    {
        $this->confirmedQuiz([
            'title'               => 'Quiz Chiuso Test',
            'enrollments_open_at' => now()->subDays(10),
            'enrollments_close_at' => now()->subDay(),
        ]);

        $viewer = $this->viewer();

        $this->actingAs($viewer)
            ->get(route('calendar.index'))
            ->assertOk()
            ->assertSee('Chiusa');
    }

    public function test_enrollment_button_not_shown_for_non_approved_viewer(): void
    {
        $this->confirmedQuiz([
            'title'               => 'Quiz Aperto Test',
            'enrollments_open_at' => null,
            'enrollments_close_at' => null,
        ]);

        $viewer = $this->viewer(User::REG_NONE);

        $this->actingAs($viewer)
            ->get(route('calendar.index'))
            ->assertOk()
            ->assertSee('Completa profilo');
    }

    // ── Tests: accessor enrollment_status ─────────────────────────────────────

    public function test_enrollment_status_accessor_returns_upcoming(): void
    {
        $quiz = $this->confirmedQuiz(['enrollments_open_at' => now()->addDay()]);
        $this->assertSame('upcoming', $quiz->enrollment_status);
    }

    public function test_enrollment_status_accessor_returns_open_when_in_window(): void
    {
        $quiz = $this->confirmedQuiz([
            'enrollments_open_at'  => now()->subDay(),
            'enrollments_close_at' => now()->addDay(),
        ]);
        $this->assertSame('open', $quiz->enrollment_status);
    }

    public function test_enrollment_status_accessor_returns_not_scheduled_when_no_dates(): void
    {
        $quiz = $this->confirmedQuiz(['enrollments_open_at' => null, 'enrollments_close_at' => null]);
        $this->assertSame('not_scheduled', $quiz->enrollment_status);
    }

    public function test_enrollment_status_accessor_returns_closed(): void
    {
        $quiz = $this->confirmedQuiz(['enrollments_close_at' => now()->subDay()]);
        $this->assertSame('closed', $quiz->enrollment_status);
    }

    // ── Tests: widget dashboard ────────────────────────────────────────────────

    public function test_dashboard_shows_next_session_widget_when_session_exists(): void
    {
        $quiz = $this->confirmedQuiz([
            'title'               => 'Prossima Sessione Test',
            'enrollments_open_at' => null,
            'enrollments_close_at' => now()->addDays(5),
        ]);

        $this->actingAs($this->viewer())
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Prossima sessione')
            ->assertSee('Prossima Sessione Test');
    }

    public function test_dashboard_widget_shows_correct_quiz_between_open_and_upcoming(): void
    {
        // Quiz aperto senza data = COALESCE restituisce NOW() come ordine
        $openQuiz = $this->confirmedQuiz([
            'title'               => 'Quiz Aperto',
            'enrollments_open_at'  => null,
            'enrollments_close_at' => null,
        ]);

        // Quiz upcoming con data futura = viene dopo l'aperto nell'ordinamento
        $upcomingQuiz = $this->confirmedQuiz([
            'title'               => 'Quiz Upcoming',
            'enrollments_open_at' => now()->addDays(3),
        ]);

        $response = $this->actingAs($this->viewer())
            ->get(route('dashboard'));

        $response->assertOk()
            ->assertViewHas('nextSession', fn ($s) => $s !== null);
    }
}
