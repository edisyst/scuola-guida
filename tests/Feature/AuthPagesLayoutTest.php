<?php

namespace Tests\Feature;

use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthPagesLayoutTest extends TestCase
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

    public function test_login_page_returns_200(): void
    {
        $this->get('/login')->assertStatus(200);
    }

    public function test_register_page_returns_200(): void
    {
        $this->get('/register')->assertStatus(200);
    }

    public function test_forgot_password_page_returns_200(): void
    {
        $this->get('/forgot-password')->assertStatus(200);
    }

    public function test_login_page_uses_guest_layout_wrapper(): void
    {
        $this->get('/login')
            ->assertStatus(200)
            ->assertSee('guest-page', false)
            ->assertSee('sg-auth-card', false);
    }

    public function test_login_page_shows_school_name_from_settings(): void
    {
        $this->setSetting('school.name', 'Autoscuola Test');

        $this->get('/login')
            ->assertStatus(200)
            ->assertSee('Autoscuola Test');
    }

    public function test_login_page_shows_logo_when_configured(): void
    {
        $this->setSetting('school.logo_path', 'logos/test-logo.png');

        $response = $this->get('/login')->assertStatus(200);
        $response->assertSee('logos/test-logo.png', false);
    }

    public function test_authenticated_user_is_redirected_away_from_login(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/login')
            ->assertRedirect();
    }

    public function test_valid_user_can_login_and_is_redirected(): void
    {
        $user = User::factory()->create();

        $response = $this->post('/login', [
            'email'    => $user->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));
    }

    public function test_invalid_password_does_not_authenticate(): void
    {
        $user = User::factory()->create();

        $this->post('/login', [
            'email'    => $user->email,
            'password' => 'wrong-password',
        ]);

        $this->assertGuest();
    }

    public function test_register_page_contains_guest_layout_markers(): void
    {
        $this->get('/register')
            ->assertStatus(200)
            ->assertSee('guest-page', false)
            ->assertSee('sg-auth-card', false);
    }

    public function test_forgot_password_page_contains_guest_layout_markers(): void
    {
        $this->get('/forgot-password')
            ->assertStatus(200)
            ->assertSee('guest-page', false)
            ->assertSee('sg-auth-card', false);
    }
}
