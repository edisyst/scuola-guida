<?php

namespace Tests\Feature;

use App\Http\Livewire\Admin\FeatureToggles;
use App\Http\Middleware\EnsureTwoFactorAuthenticated;
use App\Http\Middleware\RequireLicenseType;
use App\Models\SystemSetting;
use App\Models\User;
use App\Services\FeatureToggleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Redis;
use Livewire\Livewire;
use Tests\TestCase;

class FeatureToggleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware([EnsureTwoFactorAuthenticated::class, RequireLicenseType::class]);

        Redis::shouldReceive('get')->andReturn(null);
        Redis::shouldReceive('setex')->andReturn(true);
        Redis::shouldReceive('del')->andReturn(1);
    }

    private function seedFeatures(): void
    {
        $this->seed(\Database\Seeders\FeatureSettingSeeder::class);
    }

    private function setToggle(string $key, bool $value): void
    {
        SystemSetting::updateOrCreate(
            ['key' => "features.{$key}"],
            ['value' => $value ? '1' : '0', 'type' => 'boolean', 'group' => 'features', 'label' => $key]
        );
    }

    // ────────────────────────────────────────────────────────────────────────────
    // Admin può attivare/disattivare un toggle via componente Livewire
    // ────────────────────────────────────────────────────────────────────────────

    public function test_admin_can_toggle_feature_on(): void
    {
        $this->seedFeatures();
        $this->setToggle('gamification_enabled', false);
        $admin = User::factory()->create(['role' => 'admin']);

        Livewire::actingAs($admin)
            ->test(FeatureToggles::class)
            ->call('toggle', 'gamification_enabled')
            ->assertSet('toggles.gamification_enabled', true);

        $this->assertSame('1', SystemSetting::where('key', 'features.gamification_enabled')->value('value'));
    }

    public function test_admin_can_toggle_feature_off(): void
    {
        $this->seedFeatures();
        $admin = User::factory()->create(['role' => 'admin']);

        Livewire::actingAs($admin)
            ->test(FeatureToggles::class)
            ->call('toggle', 'gamification_enabled')
            ->assertSet('toggles.gamification_enabled', false);

        $this->assertSame('0', SystemSetting::where('key', 'features.gamification_enabled')->value('value'));
    }

    // ────────────────────────────────────────────────────────────────────────────
    // guest_homepage_enabled = false → "/" reindirizza a "/login"
    // ────────────────────────────────────────────────────────────────────────────

    public function test_guest_homepage_disabled_redirects_to_login(): void
    {
        $this->seedFeatures();
        $this->setToggle('guest_homepage_enabled', false);

        $this->get(route('guest.home'))
            ->assertRedirect(route('login'));
    }

    public function test_guest_homepage_enabled_shows_homepage(): void
    {
        $this->seedFeatures();
        $this->setToggle('guest_homepage_enabled', true);

        $this->get(route('guest.home'))
            ->assertOk();
    }

    // ────────────────────────────────────────────────────────────────────────────
    // gamification_enabled = false → badge page returns 404
    // ────────────────────────────────────────────────────────────────────────────

    public function test_badges_page_returns_404_when_gamification_disabled(): void
    {
        $this->seedFeatures();
        $this->setToggle('gamification_enabled', false);

        $viewer = User::factory()->create(['role' => 'viewer']);

        $this->actingAs($viewer)
            ->get(route('viewer.profile.badges'))
            ->assertNotFound();
    }

    // ────────────────────────────────────────────────────────────────────────────
    // Flag config-gestiti compaiono read-only: tentativo di toggle → 422
    // ────────────────────────────────────────────────────────────────────────────

    public function test_config_managed_flag_cannot_be_toggled(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        Livewire::actingAs($admin)
            ->test(FeatureToggles::class)
            ->call('toggle', 'APP_DEBUG')
            ->assertStatus(422);
    }

    // ────────────────────────────────────────────────────────────────────────────
    // La pagina /admin/system/features è visibile solo all'admin
    // ────────────────────────────────────────────────────────────────────────────

    public function test_features_page_accessible_by_admin(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->get(route('admin.system.features'))
            ->assertOk();
    }

    public function test_features_page_denied_for_non_admin(): void
    {
        $editor = User::factory()->create(['role' => 'editor']);

        $this->actingAs($editor)
            ->get(route('admin.system.features'))
            ->assertForbidden();
    }

    // ────────────────────────────────────────────────────────────────────────────
    // FeatureToggleService::isEnabled() fallback = true quando setting non esiste
    // ────────────────────────────────────────────────────────────────────────────

    public function test_feature_enabled_by_default_when_setting_missing(): void
    {
        $service = app(FeatureToggleService::class);

        $this->assertTrue($service->isEnabled('gamification_enabled'));
    }

    public function test_feature_disabled_when_setting_is_zero(): void
    {
        $this->setToggle('web_push_enabled', false);

        $service = app(FeatureToggleService::class);

        $this->assertFalse($service->isEnabled('web_push_enabled'));
    }

    // ────────────────────────────────────────────────────────────────────────────
    // FeatureToggleService::configManaged() include tutte le chiavi attese
    // ────────────────────────────────────────────────────────────────────────────

    public function test_config_managed_returns_all_expected_flags(): void
    {
        $service = app(FeatureToggleService::class);
        $flags   = array_column($service->configManaged(), 'flag');

        $this->assertContains('APP_DEBUG', $flags);
        $this->assertContains('QUEUE_CONNECTION', $flags);
        $this->assertContains('SESSION_DRIVER', $flags);
    }
}
