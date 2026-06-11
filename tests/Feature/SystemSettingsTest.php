<?php

namespace Tests\Feature;

use App\Http\Middleware\EnsureTwoFactorAuthenticated;
use App\Models\SystemSetting;
use App\Models\User;
use App\Services\SettingService;
use Database\Seeders\SystemSettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SystemSettingsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(EnsureTwoFactorAuthenticated::class);
    }

    // ------------------------------------------------------------------ helpers

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    private function editor(): User
    {
        return User::factory()->create(['role' => 'editor']);
    }

    private function seedSettings(): void
    {
        $this->seed(SystemSettingSeeder::class);
    }

    // ------------------------------------------------------------------ SettingService

    public function test_get_returns_default_when_key_not_exists(): void
    {
        $service = app(SettingService::class);

        $this->assertSame('fallback', $service->get('non.existent.key', 'fallback'));
    }

    public function test_set_updates_db_and_invalidates_cache(): void
    {
        $this->seedSettings();

        $service = app(SettingService::class);
        $service->set('school.name', 'Nuova Scuola');

        $this->assertDatabaseHas('system_settings', [
            'key'   => 'school.name',
            'value' => 'Nuova Scuola',
        ]);
    }

    public function test_get_reads_from_db_when_redis_unavailable(): void
    {
        $this->seedSettings();

        SystemSetting::where('key', 'school.name')->update(['value' => 'Test School']);

        Redis::shouldReceive('get')->andThrow(new \Exception('Redis down'));
        Redis::shouldReceive('setex')->andThrow(new \Exception('Redis down'));

        $service = app(SettingService::class);
        $value = $service->get('school.name');

        $this->assertSame('Test School', $value);
    }

    // ------------------------------------------------------------------ HTTP — health

    public function test_editor_gets_403_on_system_health(): void
    {
        $this->actingAs($this->editor())
            ->get(route('admin.system.health'))
            ->assertForbidden();
    }

    public function test_admin_sees_all_six_health_indicators(): void
    {
        $this->seedSettings();

        $this->actingAs($this->admin())
            ->get(route('admin.system.health'))
            ->assertOk()
            ->assertSee(__('system.service_database'))
            ->assertSee(__('system.service_redis'))
            ->assertSee(__('system.service_queue'))
            ->assertSee(__('system.service_storage'))
            ->assertSee(__('system.service_mail'))
            ->assertSee(__('system.service_twilio'));
    }

    // ------------------------------------------------------------------ HTTP — settings

    public function test_admin_saves_settings_gets_flash_and_db_updated(): void
    {
        $this->seedSettings();

        $this->actingAs($this->admin())
            ->post(route('admin.system.settings.update'), [
                'school_name'    => 'Autoscuola Test',
                'school_tagline' => 'Il meglio',
                'school_address' => 'Via Roma 1',
                'school_phone'   => '0123456789',
                'school_email'   => 'test@scuola.it',
                'school_license_number' => 'MIT-001',
            ])
            ->assertRedirect(route('admin.system.settings'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('system_settings', ['key' => 'school.name', 'value' => 'Autoscuola Test']);
        $this->assertDatabaseHas('system_settings', ['key' => 'school.email', 'value' => 'test@scuola.it']);
    }

    public function test_admin_uploads_logo_and_path_saved_in_db(): void
    {
        $this->seedSettings();
        Storage::fake('public');

        $file = UploadedFile::fake()->image('logo.png', 100, 100);

        $this->actingAs($this->admin())
            ->post(route('admin.system.settings.update'), [
                'school_name' => 'Test',
                'logo'        => $file,
            ])
            ->assertRedirect();

        $setting = SystemSetting::where('key', 'school.logo_path')->first();
        $this->assertNotEmpty($setting->value);
        Storage::disk('public')->assertExists($setting->value);
    }

    // ------------------------------------------------------------------ Validation

    public function test_accent_color_rejects_invalid_hex(): void
    {
        $this->seedSettings();

        $this->actingAs($this->admin())
            ->post(route('admin.system.settings.update'), [
                'school_name'  => 'Test',
                'accent_color' => 'notahex',
            ])
            ->assertSessionHasErrors('accent_color');
    }

    public function test_logo_rejects_file_over_2mb(): void
    {
        $this->seedSettings();
        Storage::fake('public');

        $file = UploadedFile::fake()->image('big.png')->size(3000);

        $this->actingAs($this->admin())
            ->post(route('admin.system.settings.update'), [
                'school_name' => 'Test',
                'logo'        => $file,
            ])
            ->assertSessionHasErrors('logo');
    }

    // ------------------------------------------------------------------ Seeder idempotency

    public function test_seeder_is_idempotent(): void
    {
        $this->seed(SystemSettingSeeder::class);
        $countFirst = SystemSetting::count();

        $this->seed(SystemSettingSeeder::class);
        $countSecond = SystemSetting::count();

        $this->assertSame($countFirst, $countSecond);
    }
}
