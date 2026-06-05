<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\RegistrazioneApprovataNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class LocalizationViewerTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        // Reset locale PRIMA di parent::tearDown() — dopo, il container non è più disponibile.
        app()->setLocale('it');
        parent::tearDown();
    }

    // ─────────────────────────────────────────────────────────────────────────
    // SetLocale middleware — users.locale ha priorità sui loggati
    // ─────────────────────────────────────────────────────────────────────────

    public function test_viewer_with_locale_en_sees_english_content(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_VIEWER, 'locale' => 'en']);

        $response = $this->actingAs($user)->get(route('simulator.index'));

        $response->assertOk();
        // viewer.simulator.title in EN = 'Exam Simulator'
        $response->assertSee('Exam Simulator');
    }

    public function test_viewer_with_locale_es_sees_spanish_content(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_VIEWER, 'locale' => 'es']);

        $response = $this->actingAs($user)->get(route('simulator.index'));

        $response->assertOk();
        // viewer.simulator.title in ES = 'Simulador Examen'
        $response->assertSee('Simulador');
    }

    public function test_viewer_with_locale_null_sees_italian_fallback_content(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_VIEWER, 'locale' => null]);

        $response = $this->actingAs($user)->get(route('simulator.index'));

        $response->assertOk();
        // viewer.simulator.title in IT = 'Simulatore Esame'
        $response->assertSee('Simulatore');
    }

    public function test_guest_with_session_locale_en_is_redirected_to_login(): void
    {
        // Il simulatore richiede autenticazione → redirect. Il middleware gira prima del redirect.
        $response = $this->withSession(['app_locale' => 'en'])
                         ->get(route('simulator.index'));

        $response->assertRedirect();
    }

    // ─────────────────────────────────────────────────────────────────────────
    // users.locale viene persistito al cambio lingua (loggato)
    // ─────────────────────────────────────────────────────────────────────────

    public function test_switch_locale_persists_to_users_locale(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_VIEWER, 'locale' => 'it']);

        $this->actingAs($user)->post(route('locale.switch'), ['locale' => 'en']);

        $this->assertEquals('en', $user->fresh()->locale);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Locale non supportato — fallback a italiano senza eccezioni
    // ─────────────────────────────────────────────────────────────────────────

    public function test_unsupported_locale_falls_back_to_italian_content(): void
    {
        // Locale 'zh' non è in config/locales.php → fallback a italiano.
        $user = User::factory()->create(['role' => User::ROLE_VIEWER, 'locale' => 'zh']);

        $response = $this->actingAs($user)->get(route('simulator.index'));

        $response->assertOk();
        $response->assertSee('Simulatore');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // User implementa HasLocalePreference
    // ─────────────────────────────────────────────────────────────────────────

    public function test_user_implements_has_locale_preference(): void
    {
        $user = User::factory()->create(['locale' => 'es']);

        $this->assertInstanceOf(
            \Illuminate\Contracts\Translation\HasLocalePreference::class,
            $user
        );
        $this->assertEquals('es', $user->preferredLocale());
    }

    public function test_preferred_locale_returns_null_when_not_set(): void
    {
        $user = User::factory()->create(['locale' => null]);

        $this->assertNull($user->preferredLocale());
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Notifica inviata a viewer con locale ES
    // ─────────────────────────────────────────────────────────────────────────

    public function test_notification_sent_to_viewer_with_es_locale(): void
    {
        Notification::fake();

        $user = User::factory()->create(['role' => User::ROLE_VIEWER, 'locale' => 'es']);

        $user->notify(new RegistrazioneApprovataNotification());

        Notification::assertSentTo($user, RegistrazioneApprovataNotification::class);
    }

    public function test_notification_subject_in_spanish(): void
    {
        app()->setLocale('es');

        $this->assertEquals(
            'Inscripción de datos personales aprobada',
            __('notifications.reg_approved_subject')
        );
    }

    public function test_notification_subject_in_english(): void
    {
        app()->setLocale('en');

        $this->assertEquals(
            'Personal data enrollment approved',
            __('notifications.reg_approved_subject')
        );
    }

    // ─────────────────────────────────────────────────────────────────────────
    // validation.php — messaggi nella lingua corretta
    // ─────────────────────────────────────────────────────────────────────────

    public function test_validation_required_in_italian(): void
    {
        app()->setLocale('it');

        $this->assertEquals(
            'Il campo :attribute è obbligatorio.',
            __('validation.required')
        );
    }

    public function test_validation_required_in_english(): void
    {
        app()->setLocale('en');

        $this->assertEquals(
            'The :attribute field is required.',
            __('validation.required')
        );
    }

    public function test_validation_required_in_spanish(): void
    {
        app()->setLocale('es');

        $this->assertEquals(
            'El campo :attribute es obligatorio.',
            __('validation.required')
        );
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Chiavi di traduzione viewer — presenza in tutte e tre le lingue
    // ─────────────────────────────────────────────────────────────────────────

    public function test_viewer_translation_keys_present_in_all_locales(): void
    {
        foreach (['it', 'en', 'es'] as $locale) {
            app()->setLocale($locale);

            $this->assertNotEquals('dashboard.title', __('dashboard.title'), "dashboard.title mancante in {$locale}");
            $this->assertNotEquals('review.smart_title', __('review.smart_title'), "review.smart_title mancante in {$locale}");
            $this->assertNotEquals('flags.report_title', __('flags.report_title'), "flags.report_title mancante in {$locale}");
            $this->assertNotEquals('profile.current_password', __('profile.current_password'), "profile.current_password mancante in {$locale}");
            $this->assertNotEquals('gamification.title', __('gamification.title'), "gamification.title mancante in {$locale}");
        }
    }
}
