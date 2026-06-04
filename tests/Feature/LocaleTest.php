<?php

namespace Tests\Feature;

use App\Models\User;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class LocaleTest extends TestCase
{
    use RefreshDatabase;

    public function test_switch_locale_saves_to_session(): void
    {
        $response = $this->post(route('locale.switch'), ['locale' => 'en']);

        $response->assertSessionHas('app_locale', 'en');
        $response->assertRedirect();
    }

    public function test_switch_locale_rejects_unsupported_locale(): void
    {
        $response = $this->post(route('locale.switch'), ['locale' => 'fr']);

        $response->assertSessionMissing('app_locale');
        $response->assertSessionHasErrors(['locale']);
        $response->assertRedirect();
    }

    public function test_middleware_applies_locale_from_session(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
                         ->withSession(['app_locale' => 'en'])
                         ->get(route('dashboard'));

        $response->assertOk();
        $this->assertEquals('en', app()->getLocale());
    }

    public function test_menu_string_translated_to_english(): void
    {
        $this->withSession(['app_locale' => 'en']);

        app()->setLocale('en');

        $this->assertEquals('Dashboard', __('menu.dashboard'));
        $this->assertEquals('Questions', __('menu.domande'));
        $this->assertEquals('Language updated.', __('menu.locale_changed'));
    }

    public function test_menu_string_translated_to_italian(): void
    {
        app()->setLocale('it');

        $this->assertEquals('Dashboard', __('menu.dashboard'));
        $this->assertEquals('Domande', __('menu.domande'));
        $this->assertEquals('Lingua aggiornata.', __('menu.locale_changed'));
    }

    public function test_switch_locale_redirects_with_info_flash(): void
    {
        $response = $this->post(route('locale.switch'), ['locale' => 'en']);

        $response->assertSessionHas('info');
        $response->assertRedirect();
    }
}
