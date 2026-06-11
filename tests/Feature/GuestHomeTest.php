<?php

namespace Tests\Feature;

use App\Models\LicenseType;
use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GuestHomeTest extends TestCase
{
    use RefreshDatabase;

    private function setSetting(string $key, string $value, string $group = 'school'): void
    {
        SystemSetting::create([
            'key'   => $key,
            'value' => $value,
            'type'  => 'string',
            'group' => $group,
            'label' => $key,
        ]);
    }

    public function test_guest_sees_school_name_from_settings(): void
    {
        $this->setSetting('school.name', 'Autoscuola Roma');

        $this->get('/')->assertStatus(200)->assertSee('Autoscuola Roma');
    }

    public function test_guest_sees_tagline_from_settings(): void
    {
        $this->setSetting('school.tagline', 'Il futuro inizia qui');

        $this->get('/')->assertStatus(200)->assertSee('Il futuro inizia qui');
    }

    public function test_authenticated_user_is_redirected_to_dashboard(): void
    {
        $user = User::factory()->create(['role' => 'viewer']);

        $this->actingAs($user)->get('/')->assertRedirect(route('dashboard'));
    }

    public function test_authenticated_admin_is_redirected_to_stats(): void
    {
        $user = User::factory()->create(['role' => 'admin']);

        $this->actingAs($user)->get('/')->assertRedirect(route('admin.stats'));
    }

    public function test_authenticated_editor_is_redirected_to_editor_dashboard(): void
    {
        $user = User::factory()->create(['role' => 'editor']);

        $this->actingAs($user)->get('/')->assertRedirect(route('editor.dashboard'));
    }

    public function test_stats_section_hidden_when_all_counts_are_zero(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertDontSee(__('guest.stat_quiz'));
    }

    public function test_license_types_section_hidden_when_only_one_type(): void
    {
        LicenseType::query()->delete();
        LicenseType::factory()->create(['is_active' => true, 'sort_order' => 1]);

        $this->get('/')->assertStatus(200)->assertDontSee(__('guest.license_types_title'));
    }

    public function test_view_renders_when_logo_path_is_null(): void
    {
        $this->setSetting('school.logo_path', '');

        $this->get('/')->assertStatus(200);
    }

    public function test_view_renders_when_tagline_is_null(): void
    {
        $this->get('/')->assertStatus(200);
    }

    public function test_italian_lang_keys_present(): void
    {
        $keys = require base_path('lang/it/guest.php');

        $this->assertArrayHasKey('nav_login', $keys);
        $this->assertArrayHasKey('hero_tagline_default', $keys);
        $this->assertArrayHasKey('final_cta_button', $keys);
    }

    public function test_english_lang_keys_present(): void
    {
        $keys = require base_path('lang/en/guest.php');

        $this->assertArrayHasKey('nav_login', $keys);
        $this->assertArrayHasKey('hero_tagline_default', $keys);
        $this->assertArrayHasKey('final_cta_button', $keys);
    }

    public function test_spanish_lang_keys_present(): void
    {
        $keys = require base_path('lang/es/guest.php');

        $this->assertArrayHasKey('nav_login', $keys);
        $this->assertArrayHasKey('hero_tagline_default', $keys);
        $this->assertArrayHasKey('final_cta_button', $keys);
    }

    public function test_license_types_section_shown_when_multiple_types(): void
    {
        LicenseType::factory()->count(3)->create(['is_active' => true]);

        $this->get('/')->assertStatus(200)->assertSee(__('guest.license_types_title'));
    }
}
