<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;

class CategoryTest extends TestCase
{
    use RefreshDatabase;

    protected function adminUser()
    {
        return \App\Models\User::factory()->create([
            'role' => 'admin'
        ]);
    }

    protected function editorUser()
    {
        return \App\Models\User::factory()->create([
            'role' => 'editor'
        ]);
    }

    protected function viewerUser()
    {
        return \App\Models\User::factory()->create([
            'role' => 'viewer'
        ]);
    }

    public function test_guest_cannot_access_admin()
    {
        $response = $this->get('/admin/questions');

        $response->assertRedirect('/login');
    }

    public function test_only_admin_can_access_audit()
    {
        $this->actingAs($this->editorUser());

        $response = $this->get(route('admin.audit.index'));

        $response->assertStatus(403);
    }

    public function test_admin_can_create_category()
    {
        $this->actingAs($this->adminUser());

        $response = $this->post(route('admin.categories.store'), [
            'name' => 'Nuova categoria'
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('categories', [
            'name' => 'Nuova categoria'
        ]);
    }

    public function test_non_admin_cannot_access_categories()
    {
        $user = User::factory()->create(['role' => 'guest']);

        $response = $this->actingAs($user)->get('/admin/categories');

        $response->assertStatus(403);
    }
}
