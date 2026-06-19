<?php

namespace Tests\Feature;

use App\Http\Middleware\EnsureTwoFactorAuthenticated;
use App\Models\SystemSetting;
use App\Models\User;
use Database\Seeders\SystemSettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Redis;
use Tests\TestCase;

/**
 * Feature 15.0: la configurabilità appearance è stata rimossa.
 * I colori e il font sono costanti del design system in scuola-guida.css.
 * I test che validavano il salvataggio di accent/font/radius/skin sono stati
 * rimossi; rimangono i test di autorizzazione e di assenza configurabilità.
 */
class AppearanceSettingsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(EnsureTwoFactorAuthenticated::class);
        $this->seed(SystemSettingSeeder::class);

        // Bypass Redis cache so reads always hit the DB
        Redis::shouldReceive('get')->andReturn(null);
        Redis::shouldReceive('setex')->andReturn(true);
        Redis::shouldReceive('del')->andReturn(1);
    }

    public function test_submitting_appearance_fields_does_not_persist_them(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->post(route('admin.system.settings.update'), [
                'accent_color'       => '#112233',
                'font_family'        => 'inter',
                'border_radius'      => 'rounded',
                'sidebar_skin_admin' => 'sidebar-dark-indigo',
            ])
            ->assertRedirect(route('admin.system.settings'));

        // I valori appearance.* non devono essere stati modificati dal form
        $this->assertNotSame('#112233', SystemSetting::where('key', 'appearance.accent_color')->value('value'));
        $this->assertNotSame('inter', SystemSetting::where('key', 'appearance.font_family')->value('value'));
        $this->assertNotSame('rounded', SystemSetting::where('key', 'appearance.border_radius')->value('value'));
        $this->assertNotSame('sidebar-dark-indigo', SystemSetting::where('key', 'appearance.sidebar_skin_admin')->value('value'));
    }

    public function test_accent_color_is_now_a_css_constant_not_dynamic(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)
            ->get(route('admin.system.settings'))
            ->assertOk();

        // --sg-accent non deve essere iniettato dinamicamente nella risposta HTTP
        $response->assertDontSee('--sg-accent: #', false);
    }

    public function test_non_admin_cannot_update_settings(): void
    {
        $editor = User::factory()->create(['role' => 'editor']);

        $this->actingAs($editor)
            ->post(route('admin.system.settings.update'), ['school_name' => 'Hack'])
            ->assertForbidden();
    }
}
