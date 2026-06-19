<?php

namespace Tests\Feature;

use App\Http\Middleware\EnsureTwoFactorAuthenticated;
use App\Models\User;
use Database\Seeders\SystemSettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RedesignShellTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(EnsureTwoFactorAuthenticated::class);
        $this->seed(SystemSettingSeeder::class);
    }

    private function user(string $role): User
    {
        return User::factory()->create(['role' => $role]);
    }

    /** La sidebar non usa più classi saturate per ruolo. */
    public function test_admin_dashboard_has_no_saturated_sidebar_class(): void
    {
        $response = $this->actingAs($this->user('admin'))
            ->get(route('dashboard'))
            ->assertOk();

        $response->assertDontSee('sidebar-dark-danger', false);
        $response->assertDontSee('sidebar-dark-warning', false);
        $response->assertDontSee('sidebar-dark-success', false);
    }

    /** La sidebar usa la classe uniforme sidebar-dark-primary per tutti i ruoli. */
    public function test_sidebar_uses_uniform_class_for_all_roles(): void
    {
        foreach (['admin', 'editor', 'viewer', 'instructor'] as $role) {
            $response = $this->actingAs($this->user($role))
                ->get(route('dashboard'))
                ->assertOk();

            $response->assertSee('sidebar-dark-primary', false);
        }
    }

    /** Badge ruolo admin presente nella pagina (classe role-admin). */
    public function test_admin_page_contains_role_badge(): void
    {
        $response = $this->actingAs($this->user('admin'))
            ->get(route('dashboard'))
            ->assertOk();

        $response->assertSee('role-admin', false);
        $response->assertSee('sg-role-badge', false);
    }

    /** Badge ruolo editor presente nella pagina. */
    public function test_editor_page_contains_role_badge(): void
    {
        $response = $this->actingAs($this->user('editor'))
            ->get(route('dashboard'))
            ->assertOk();

        $response->assertSee('role-editor', false);
        $response->assertSee('sg-role-badge', false);
    }

    /** Badge ruolo viewer presente nella pagina. */
    public function test_viewer_page_contains_role_badge(): void
    {
        $response = $this->actingAs($this->user('viewer'))
            ->get(route('dashboard'))
            ->assertOk();

        $response->assertSee('role-viewer', false);
        $response->assertSee('sg-role-badge', false);
    }

    /** Badge ruolo instructor presente nella pagina. */
    public function test_instructor_page_contains_role_badge(): void
    {
        $response = $this->actingAs($this->user('instructor'))
            ->get(route('dashboard'))
            ->assertOk();

        $response->assertSee('role-instructor', false);
        $response->assertSee('sg-role-badge', false);
    }

    /** La navbar usa la classe sg-navbar uniforme per tutti i ruoli. */
    public function test_navbar_uses_uniform_sg_navbar_class(): void
    {
        foreach (['admin', 'editor', 'viewer', 'instructor'] as $role) {
            $response = $this->actingAs($this->user($role))
                ->get(route('dashboard'))
                ->assertOk();

            $response->assertSee('sg-navbar', false);
            $response->assertDontSee('navbar-danger', false);
            $response->assertDontSee('navbar-success', false);
            $response->assertDontSee('navbar-warning', false);
        }
    }

    /** Il body ha la classe role-{ruolo} per il selettore CSS di accento. */
    public function test_body_has_role_class_for_css_accent(): void
    {
        $cases = [
            'admin'      => 'role-admin',
            'editor'     => 'role-editor',
            'viewer'     => 'role-viewer',
            'instructor' => 'role-instructor',
        ];

        foreach ($cases as $role => $bodyClass) {
            $response = $this->actingAs($this->user($role))
                ->get(route('dashboard'))
                ->assertOk();

            $response->assertSee($bodyClass, false);
        }
    }

    /** Le pagine principali rispondono 200 per admin. */
    public function test_main_pages_respond_200_for_admin(): void
    {
        $admin = $this->user('admin');

        $routes = [
            route('dashboard'),
            route('admin.questions.index'),
            route('admin.users.index'),
        ];

        foreach ($routes as $url) {
            $this->actingAs($admin)->get($url)->assertOk();
        }
    }

    /** Le pagine principali rispondono 200 per editor. */
    public function test_main_pages_respond_200_for_editor(): void
    {
        $editor = $this->user('editor');

        $this->actingAs($editor)->get(route('dashboard'))->assertOk();
        $this->actingAs($editor)->get(route('admin.questions.index'))->assertOk();
    }

    /** Le pagine principali rispondono 200 per viewer. */
    public function test_main_pages_respond_200_for_viewer(): void
    {
        $viewer = $this->user('viewer');

        $this->actingAs($viewer)->get(route('dashboard'))->assertOk();
    }

    /** Le pagine principali rispondono 200 per instructor. */
    public function test_main_pages_respond_200_for_instructor(): void
    {
        $instructor = $this->user('instructor');

        $this->actingAs($instructor)->get(route('dashboard'))->assertOk();
    }
}
