<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\SystemSettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RedesignGuestTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(SystemSettingSeeder::class);
    }

    // ── 1. Homepage risponde 200 e usa la nuova struttura hero ────────────────

    public function test_homepage_returns_ok(): void
    {
        $this->get(route('guest.home'))
            ->assertOk();
    }

    public function test_homepage_hero_usa_nuova_struttura_sg_hero(): void
    {
        $this->get(route('guest.home'))
            ->assertOk()
            ->assertSee('sg-hero', false)
            ->assertSee('sg-hero-content', false)
            ->assertSee('sg-hero-title', false)
            ->assertSee('sg-hero-actions', false);
    }

    public function test_homepage_hero_non_contiene_box_annidati_vecchi(): void
    {
        $this->get(route('guest.home'))
            ->assertOk()
            ->assertDontSee('sg-hero-overlay-text', false)
            ->assertDontSee('sg-hero-overlay-soft', false)
            ->assertDontSee('sg-hero-overlay-cta', false);
    }

    public function test_homepage_feature_card_usa_nuova_classe(): void
    {
        $this->get(route('guest.home'))
            ->assertOk()
            ->assertSee('sg-feature-card', false)
            ->assertSee('sg-feature-icon', false);
    }

    public function test_homepage_cta_finale_usa_classe_token(): void
    {
        $this->get(route('guest.home'))
            ->assertOk()
            ->assertSee('sg-cta-section', false)
            ->assertSee('sg-btn-cta', false);
    }

    // ── 2. Pagine auth rispondono 200 e usano classi token ────────────────────

    public function test_login_page_returns_ok(): void
    {
        $this->get(route('login'))
            ->assertOk();
    }

    public function test_login_page_usa_sg_auth_card(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSee('sg-auth-card', false)
            ->assertSee('sg-auth-center', false);
    }

    public function test_register_page_returns_ok(): void
    {
        $this->get(route('register'))
            ->assertOk();
    }

    public function test_register_page_usa_sg_auth_card(): void
    {
        $this->get(route('register'))
            ->assertOk()
            ->assertSee('sg-auth-card', false);
    }

    // ── 3. Flusso login non regredito ─────────────────────────────────────────

    public function test_login_con_credenziali_valide_redirige_alla_dashboard(): void
    {
        $user = User::factory()->create([
            'role'     => 'viewer',
            'password' => bcrypt('password'),
        ]);

        $this->post(route('login'), [
            'email'    => $user->email,
            'password' => 'password',
        ])->assertRedirect();

        $this->assertAuthenticatedAs($user);
    }

    public function test_login_con_credenziali_errate_torna_con_errore(): void
    {
        User::factory()->create([
            'role'     => 'viewer',
            'email'    => 'test@example.com',
            'password' => bcrypt('correctpassword'),
        ]);

        $this->post(route('login'), [
            'email'    => 'test@example.com',
            'password' => 'wrongpassword',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }
}
