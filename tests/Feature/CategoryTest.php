<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;

class CategoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_example(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
    }

    public function test_admin_can_create_category()
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $response = $this->actingAs($admin)->post('/admin/categories', [
            'name' => 'Segnali'
        ]);

        $response->assertRedirect('/admin/categories');

        $this->assertDatabaseHas('categories', [
            'name' => 'Segnali'
        ]);
    }

    public function test_non_admin_cannot_access_categories()
    {
        $user = User::factory()->create(['is_admin' => false]);

        $response = $this->actingAs($user)->get('/admin/categories');

        $response->assertStatus(403);
    }
}
