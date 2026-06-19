<?php

namespace Tests\Feature;

use App\Http\Middleware\EnsureTwoFactorAuthenticated;
use App\Models\User;
use Database\Seeders\SystemSettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class DesignSystemFoundationsTest extends TestCase
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
        return User::factory()->create(['role' => 'admin']);
    }

    private function viewer(): User
    {
        return User::factory()->create(['role' => 'viewer']);
    }

    // ── 1. Il pannello settings non espone più controlli appearance ──────────

    public function test_settings_panel_does_not_contain_accent_color_control(): void
    {
        $this->actingAs($this->admin())
            ->get(route('admin.system.settings'))
            ->assertOk()
            ->assertDontSee('accent_color', false)
            ->assertDontSee('font_family', false)
            ->assertDontSee('border_radius', false)
            ->assertDontSee('sidebar_skin_admin', false);
    }

    public function test_settings_panel_still_contains_school_fields(): void
    {
        $this->actingAs($this->admin())
            ->get(route('admin.system.settings'))
            ->assertOk()
            ->assertSee('school_name', false)
            ->assertSee('school_email', false);
    }

    public function test_settings_panel_logo_upload_still_present(): void
    {
        $this->actingAs($this->admin())
            ->get(route('admin.system.settings'))
            ->assertOk()
            ->assertSee('name="logo"', false);
    }

    // ── 2. Area guest e admin includono il token --sg-font / Inter ──────────

    public function test_guest_home_returns_200(): void
    {
        $this->get(route('guest.home'))
            ->assertOk();
    }

    public function test_guest_home_includes_inter_font(): void
    {
        $this->get(route('guest.home'))
            ->assertOk()
            ->assertSee('Inter', false);
    }

    public function test_admin_settings_page_includes_sg_font_token(): void
    {
        $this->actingAs($this->admin())
            ->get(route('admin.system.settings'))
            ->assertOk()
            ->assertSee('Inter', false);
    }

    public function test_scuola_guida_css_defines_sg_font_token(): void
    {
        $css = file_get_contents(public_path('css/scuola-guida.css'));

        $this->assertStringContainsString('--sg-font:', $css);
        $this->assertStringContainsString('Inter', $css);
    }

    public function test_scuola_guida_css_defines_shell_tokens(): void
    {
        $css = file_get_contents(public_path('css/scuola-guida.css'));

        $this->assertStringContainsString('--sg-shell-bg:', $css);
        $this->assertStringContainsString('--sg-shell-text:', $css);
        $this->assertStringContainsString('--sg-accent:', $css);
    }

    public function test_scuola_guida_css_defines_role_tokens(): void
    {
        $css = file_get_contents(public_path('css/scuola-guida.css'));

        $this->assertStringContainsString('--sg-role-admin:', $css);
        $this->assertStringContainsString('--sg-role-editor:', $css);
        $this->assertStringContainsString('--sg-role-viewer:', $css);
        $this->assertStringContainsString('--sg-role-instructor:', $css);
    }

    public function test_scuola_guida_css_radius_is_constant(): void
    {
        $css = file_get_contents(public_path('css/scuola-guida.css'));

        $this->assertStringContainsString('--sg-radius:', $css);
        $this->assertStringNotContainsString('--sg-radius: 14px', $css);
    }

    // ── 3. Migration di rimozione appearance.* è reversibile ────────────────

    public function test_deprecate_appearance_migration_is_reversible(): void
    {
        Artisan::call('migrate', ['--path' => 'database/migrations/2026_06_19_130000_deprecate_appearance_settings.php']);
        Artisan::call('migrate:rollback', ['--path' => 'database/migrations/2026_06_19_130000_deprecate_appearance_settings.php']);

        $this->assertDatabaseHas('system_settings', ['key' => 'appearance.accent_color']);
        $this->assertDatabaseHas('system_settings', ['key' => 'appearance.font_family']);
        $this->assertDatabaseHas('system_settings', ['key' => 'appearance.sidebar_skin_admin']);
    }

    public function test_appearance_css_partial_does_not_inject_dynamic_colors(): void
    {
        $partial = file_get_contents(resource_path('views/layouts/partials/appearance-css.blade.php'));

        $this->assertStringNotContainsString('setting(', $partial);
        $this->assertStringNotContainsString('--sg-accent:', $partial);
        $this->assertStringNotContainsString('readableTextColor', $partial);
    }
}
