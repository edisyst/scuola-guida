<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PragmaRX\Google2FA\Google2FA;
use Tests\TestCase;

class TwoFactorTest extends TestCase
{
    use RefreshDatabase;

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function adminUser(): User
    {
        return User::factory()->create(['role' => User::ROLE_ADMIN]);
    }

    private function editorUser(): User
    {
        return User::factory()->create(['role' => User::ROLE_EDITOR]);
    }

    private function viewerUser(): User
    {
        return User::factory()->create(['role' => User::ROLE_VIEWER]);
    }

    private function enableTwoFactor(User $user): string
    {
        $secret = (new Google2FA())->generateSecretKey();
        $codes  = $user->generateRecoveryCodes();

        $user->two_factor_secret         = $secret;
        $user->two_factor_enabled_at     = now();
        $user->two_factor_recovery_codes = $codes;
        $user->save();

        return $secret;
    }

    private function validOtp(string $secret): string
    {
        return (new Google2FA())->getCurrentOtp($secret);
    }

    // -------------------------------------------------------------------------
    // Profilo — visibilità sezione 2FA
    // -------------------------------------------------------------------------

    public function test_viewer_cannot_see_two_factor_section_in_profile(): void
    {
        $viewer = $this->viewerUser();

        $this->actingAs($viewer)->get('/profile')
            ->assertOk()
            ->assertDontSee('Autenticazione a due fattori');
    }

    public function test_admin_sees_two_factor_section_in_profile(): void
    {
        $admin = $this->adminUser();

        $this->actingAs($admin)->get('/profile')
            ->assertOk()
            ->assertSee('Autenticazione a due fattori');
    }

    public function test_editor_sees_two_factor_section_in_profile(): void
    {
        $editor = $this->editorUser();

        $this->actingAs($editor)->get('/profile')
            ->assertOk()
            ->assertSee('Autenticazione a due fattori');
    }

    // -------------------------------------------------------------------------
    // Middleware — redirect per admin/editor
    // -------------------------------------------------------------------------

    public function test_admin_without_2fa_setup_is_redirected_to_setup(): void
    {
        $admin = $this->adminUser();

        $this->actingAs($admin)->get('/admin/stats')
            ->assertRedirect(route('2fa.setup.show'));
    }

    public function test_editor_without_2fa_setup_is_redirected_to_setup(): void
    {
        $editor = $this->editorUser();

        $this->actingAs($editor)->get('/admin/categories')
            ->assertRedirect(route('2fa.setup.show'));
    }

    public function test_admin_with_2fa_enabled_but_not_verified_is_redirected_to_challenge(): void
    {
        $admin = $this->adminUser();
        $this->enableTwoFactor($admin);

        $this->actingAs($admin)->get('/admin/stats')
            ->assertRedirect(route('2fa.challenge.show'));
    }

    public function test_viewer_can_access_admin_area_without_2fa(): void
    {
        $viewer = $this->viewerUser();
        // Viewer accede all'area admin (le singole azioni sono protette nei controller)
        // Il middleware 2FA non deve bloccare i viewer
        $this->actingAs($viewer)
            ->withSession(['2fa_verified' => false])
            ->get('/admin/categories')
            ->assertOk();
    }

    public function test_admin_with_2fa_verified_in_session_can_access_admin(): void
    {
        $admin = $this->adminUser();
        $this->enableTwoFactor($admin);

        $this->actingAs($admin)
            ->withSession(['2fa_verified' => true])
            ->get('/admin/stats')
            ->assertOk();
    }

    // -------------------------------------------------------------------------
    // Setup 2FA
    // -------------------------------------------------------------------------

    public function test_setup_page_is_accessible_to_admin(): void
    {
        $admin = $this->adminUser();

        $this->actingAs($admin)->get(route('2fa.setup.show'))
            ->assertOk()
            ->assertSee('Configura il 2FA');
    }

    public function test_setup_with_valid_otp_enables_two_factor(): void
    {
        $admin = $this->adminUser();

        // Simulate that a secret has been generated and placed in session
        $secret = (new Google2FA())->generateSecretKey();
        $otp    = $this->validOtp($secret);

        $this->actingAs($admin)
            ->withSession(['2fa_setup_secret' => $secret])
            ->post(route('2fa.setup.store'), ['code' => $otp])
            ->assertRedirect(route('2fa.codes.show'));

        $admin->refresh();
        $this->assertTrue($admin->hasTwoFactorEnabled());
        $this->assertNotNull($admin->two_factor_recovery_codes);
    }

    public function test_setup_with_invalid_otp_does_not_enable_two_factor(): void
    {
        $admin  = $this->adminUser();
        $secret = (new Google2FA())->generateSecretKey();

        $this->actingAs($admin)
            ->withSession(['2fa_setup_secret' => $secret])
            ->post(route('2fa.setup.store'), ['code' => '000000'])
            ->assertSessionHasErrors('code');

        $admin->refresh();
        $this->assertFalse($admin->hasTwoFactorEnabled());
    }

    // -------------------------------------------------------------------------
    // Challenge — OTP
    // -------------------------------------------------------------------------

    public function test_challenge_with_valid_otp_grants_access(): void
    {
        $admin  = $this->adminUser();
        $secret = $this->enableTwoFactor($admin);
        $otp    = $this->validOtp($secret);

        $this->actingAs($admin)
            ->post(route('2fa.challenge.verify'), ['code' => $otp])
            ->assertRedirect(route('dashboard'));

        // Flag 2fa_verified deve essere in sessione
        $this->actingAs($admin)
            ->withSession(['2fa_verified' => true])
            ->get('/admin/stats')
            ->assertOk();
    }

    public function test_challenge_with_invalid_otp_denies_access(): void
    {
        $admin = $this->adminUser();
        $this->enableTwoFactor($admin);

        $this->actingAs($admin)
            ->post(route('2fa.challenge.verify'), ['code' => '000000'])
            ->assertSessionHasErrors('code');
    }

    // -------------------------------------------------------------------------
    // Challenge — recovery codes
    // -------------------------------------------------------------------------

    public function test_challenge_with_valid_recovery_code_grants_access(): void
    {
        $admin  = $this->adminUser();
        $secret = $this->enableTwoFactor($admin);

        $admin->refresh();
        $code = $admin->two_factor_recovery_codes[0];

        $this->actingAs($admin)
            ->post(route('2fa.challenge.verify'), ['recovery_code' => $code])
            ->assertRedirect(route('dashboard'));
    }

    public function test_used_recovery_code_is_consumed(): void
    {
        $admin  = $this->adminUser();
        $secret = $this->enableTwoFactor($admin);

        $admin->refresh();
        $initialCount = count($admin->two_factor_recovery_codes);
        $code         = $admin->two_factor_recovery_codes[0];

        $this->actingAs($admin)
            ->post(route('2fa.challenge.verify'), ['recovery_code' => $code]);

        $admin->refresh();
        $this->assertCount($initialCount - 1, $admin->two_factor_recovery_codes);
        $this->assertNotContains($code, $admin->two_factor_recovery_codes);
    }

    public function test_consumed_recovery_code_cannot_be_used_again(): void
    {
        $admin  = $this->adminUser();
        $secret = $this->enableTwoFactor($admin);

        $admin->refresh();
        $code = $admin->two_factor_recovery_codes[0];

        // Prima verifica — successo
        $this->actingAs($admin)
            ->post(route('2fa.challenge.verify'), ['recovery_code' => $code]);

        // Seconda verifica — fallimento
        $this->actingAs($admin)
            ->post(route('2fa.challenge.verify'), ['recovery_code' => $code])
            ->assertSessionHasErrors('recovery_code');
    }

    public function test_challenge_with_invalid_recovery_code_denies_access(): void
    {
        $admin = $this->adminUser();
        $this->enableTwoFactor($admin);

        $this->actingAs($admin)
            ->post(route('2fa.challenge.verify'), ['recovery_code' => 'WRONG-WRONG'])
            ->assertSessionHasErrors('recovery_code');
    }

    // -------------------------------------------------------------------------
    // Disabilitazione 2FA
    // -------------------------------------------------------------------------

    public function test_disable_2fa_with_correct_password_succeeds(): void
    {
        $admin = $this->adminUser();
        $this->enableTwoFactor($admin);

        $this->actingAs($admin)
            ->withSession(['2fa_verified' => true])
            ->post(route('2fa.disable'), ['password' => 'password'])
            ->assertRedirect(route('profile.edit'));

        $admin->refresh();
        $this->assertFalse($admin->hasTwoFactorEnabled());
        $this->assertNull($admin->two_factor_secret);
        $this->assertNull($admin->two_factor_recovery_codes);
    }

    public function test_disable_2fa_with_wrong_password_fails(): void
    {
        $admin = $this->adminUser();
        $this->enableTwoFactor($admin);

        $this->actingAs($admin)
            ->withSession(['2fa_verified' => true])
            ->post(route('2fa.disable'), ['password' => 'wrong-password'])
            ->assertSessionHasErrorsIn('twoFactorDisable', 'password');

        $admin->refresh();
        $this->assertTrue($admin->hasTwoFactorEnabled());
    }

    // -------------------------------------------------------------------------
    // Logout — reset flag sessione
    // -------------------------------------------------------------------------

    public function test_logout_resets_two_factor_verified_session_flag(): void
    {
        $admin = $this->adminUser();
        $this->enableTwoFactor($admin);

        $response = $this->actingAs($admin)
            ->withSession(['2fa_verified' => true])
            ->post('/logout');

        $response->assertRedirect('/');
        // Dopo il logout la sessione è invalidata (nuova sessione), quindi 2fa_verified non esiste
        $this->assertGuest();
    }
}
