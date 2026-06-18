<?php

namespace Tests\Feature;

use App\Models\Quiz;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CssCentralizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_homepage_returns_200(): void
    {
        $this->get('/')->assertStatus(200);
    }

    public function test_homepage_uses_container_not_inline_width(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertDontSee('width:80%', false);
        $response->assertDontSee('margin:0 auto', false);
    }

    public function test_homepage_has_no_inline_hero_overlay_styles(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertDontSee('backdrop-filter:blur', false);
        $response->assertDontSee('rgba(0,0,0,0.45)', false);
    }

    public function test_login_page_returns_200(): void
    {
        $this->get('/login')->assertStatus(200);
    }

    public function test_register_page_returns_200(): void
    {
        $this->get('/register')->assertStatus(200);
    }

    public function test_admin_dashboard_accessible_to_admin(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->get('/dashboard')
            ->assertStatus(200);
    }

    public function test_search_returns_200_for_authenticated_user(): void
    {
        $user = User::factory()->create(['role' => 'admin']);

        $this->actingAs($user)
            ->get('/search?q=test')
            ->assertStatus(200);
    }

    public function test_study_index_does_not_return_server_error(): void
    {
        $user = User::factory()->create(['role' => 'viewer']);

        // Viewer may be redirected if not enrolled — verify no 5xx error
        $response = $this->actingAs($user)->get(route('study.index'));

        $this->assertContains($response->getStatusCode(), [200, 302]);
    }

    public function test_scuola_guida_css_exists(): void
    {
        $this->assertFileExists(public_path('css/scuola-guida.css'));
    }

    public function test_scuola_guida_css_contains_xcloak_rule(): void
    {
        $css = file_get_contents(public_path('css/scuola-guida.css'));

        $this->assertStringContainsString('[x-cloak]', $css);
        $this->assertStringContainsString('display: none !important', $css);
    }

    public function test_scuola_guida_css_contains_navbar_logo_rule(): void
    {
        $css = file_get_contents(public_path('css/scuola-guida.css'));

        $this->assertStringContainsString('.navbar-brand img.school-logo', $css);
    }

    public function test_scuola_guida_css_contains_question_img_rule(): void
    {
        $css = file_get_contents(public_path('css/scuola-guida.css'));

        $this->assertStringContainsString('.sg-question-img', $css);
    }

    public function test_scuola_guida_css_contains_hero_overlay_rule(): void
    {
        $css = file_get_contents(public_path('css/scuola-guida.css'));

        $this->assertStringContainsString('.sg-hero-overlay', $css);
    }

    public function test_scuola_guida_css_contains_navbar_height_var(): void
    {
        $css = file_get_contents(public_path('css/scuola-guida.css'));

        $this->assertStringContainsString('--sg-navbar-height', $css);
    }

    public function test_scuola_guida_css_contains_auth_center_rule(): void
    {
        $css = file_get_contents(public_path('css/scuola-guida.css'));

        $this->assertStringContainsString('.sg-auth-center', $css);
    }

    public function test_welcome_blade_is_deleted(): void
    {
        $this->assertFileDoesNotExist(resource_path('views/welcome.blade.php'));
    }
}
