<?php

namespace Tests\Feature;

use App\Http\Middleware\EnsureTwoFactorAuthenticated;
use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Redis;
use Tests\TestCase;

class AppearanceSettingsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(EnsureTwoFactorAuthenticated::class);

        // Bypass Redis cache so reads always hit the DB
        Redis::shouldReceive('get')->andReturn(null);
        Redis::shouldReceive('setex')->andReturn(true);
        Redis::shouldReceive('del')->andReturn(1);
    }

    private function seedAppearance(): void
    {
        $this->seed(\Database\Seeders\SystemSettingSeeder::class);
        $this->artisan('migrate', [
            '--path' => 'database/migrations/2026_06_18_000000_seed_appearance_settings.php',
        ]);
    }

    public function test_admin_can_save_new_appearance_settings(): void
    {
        $this->seedAppearance();
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->post(route('admin.system.settings.update'), [
                'accent_color'            => '#112233',
                'accent_color_dark'       => '#445566',
                'font_family'             => 'inter',
                'border_radius'           => 'rounded',
                'sidebar_skin_admin'      => 'sidebar-dark-indigo',
                'sidebar_skin_editor'     => 'sidebar-dark-primary',
                'sidebar_skin_viewer'     => 'sidebar-light-info',
                'sidebar_skin_instructor' => 'sidebar-dark-success',
            ])
            ->assertRedirect(route('admin.system.settings'));

        $this->assertSame('#445566', SystemSetting::where('key', 'appearance.accent_color_dark')->value('value'));
        $this->assertSame('inter', SystemSetting::where('key', 'appearance.font_family')->value('value'));
        $this->assertSame('rounded', SystemSetting::where('key', 'appearance.border_radius')->value('value'));
        $this->assertSame('sidebar-dark-indigo', SystemSetting::where('key', 'appearance.sidebar_skin_admin')->value('value'));
    }

    public function test_invalid_font_family_is_rejected(): void
    {
        $this->seedAppearance();
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->post(route('admin.system.settings.update'), ['font_family' => 'comic-sans'])
            ->assertSessionHasErrors('font_family');
    }

    public function test_accent_color_is_rendered_as_css_variable(): void
    {
        $this->seedAppearance();
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->post(route('admin.system.settings.update'), ['accent_color' => '#abcdef']);

        $this->actingAs($admin)
            ->get(route('admin.system.settings'))
            ->assertOk()
            ->assertSee('--sg-accent: #abcdef', false);
    }

    public function test_sidebar_skin_reflects_configured_value(): void
    {
        $this->seedAppearance();
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->post(route('admin.system.settings.update'), ['sidebar_skin_admin' => 'sidebar-dark-indigo']);

        $this->actingAs($admin)
            ->get(route('admin.system.settings'))
            ->assertOk()
            ->assertSee('sidebar-dark-indigo', false);
    }

    public function test_non_admin_cannot_update_appearance(): void
    {
        $editor = User::factory()->create(['role' => 'editor']);

        $this->actingAs($editor)
            ->post(route('admin.system.settings.update'), ['accent_color' => '#000000'])
            ->assertForbidden();
    }
}
