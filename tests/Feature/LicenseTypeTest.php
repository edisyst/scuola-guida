<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\LicenseType;
use App\Models\Quiz;
use App\Models\User;
use Database\Seeders\LicenseTypeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LicenseTypeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(\App\Http\Middleware\EnsureTwoFactorAuthenticated::class);
    }

    protected function adminUser()
    {
        return User::factory()->create(['role' => 'admin']);
    }

    protected function editorUser()
    {
        return User::factory()->create(['role' => 'editor']);
    }

    public function test_seeder_inserts_all_license_types()
    {
        $this->seed(LicenseTypeSeeder::class);

        $this->assertDatabaseCount('license_types', 17);
        $this->assertDatabaseHas('license_types', ['code' => 'B', 'is_active' => true]);
        $this->assertDatabaseHas('license_types', ['code' => 'A', 'is_active' => true]);
    }

    public function test_seeder_license_type_b_has_exam_format()
    {
        $this->seed(LicenseTypeSeeder::class);

        $b = LicenseType::where('code', 'B')->first();

        $this->assertEquals(30, $b->exam_questions);
        $this->assertEquals(20, $b->exam_minutes);
        $this->assertEquals(3, $b->exam_max_errors);
    }

    public function test_migration_retrocompatibility_type_b_exists()
    {
        $b = LicenseType::where('code', 'B')->first();
        $this->assertNotNull($b);
        $this->assertEquals('Patente B', $b->name);
        $this->assertEquals(30, $b->exam_questions);
        $this->assertEquals(20, $b->exam_minutes);
        $this->assertEquals(3, $b->exam_max_errors);
    }

    public function test_admin_can_access_license_types_index()
    {
        $this->actingAs($this->adminUser());

        $response = $this->get(route('admin.license-types.index'));

        $response->assertStatus(200);
    }

    public function test_editor_cannot_access_license_types_index()
    {
        $this->actingAs($this->editorUser());

        $response = $this->get(route('admin.license-types.index'));

        $response->assertStatus(403);
    }

    public function test_admin_can_create_license_type()
    {
        $this->actingAs($this->adminUser());

        $response = $this->post(route('admin.license-types.store'), [
            'code' => 'TEST',
            'name' => 'Test License Type',
            'exam_questions' => 25,
            'exam_minutes' => 15,
            'exam_max_errors' => 2,
            'sort_order' => 99,
            'is_active' => true,
        ]);

        $response->assertRedirect(route('admin.license-types.index'));
        $this->assertDatabaseHas('license_types', ['code' => 'TEST']);
    }

    public function test_admin_can_create_license_type_with_categories()
    {
        $categories = Category::factory()->count(2)->create();
        $this->actingAs($this->adminUser());

        $this->post(route('admin.license-types.store'), [
            'code' => 'C1',
            'name' => 'Patente C1',
            'category_ids' => $categories->pluck('id')->toArray(),
        ]);

        $licenseType = LicenseType::where('code', 'C1')->first();
        $this->assertEquals(2, $licenseType->categories()->count());
    }

    public function test_admin_can_update_license_type()
    {
        $licenseType = LicenseType::factory()->create(['code' => 'D']);
        $this->actingAs($this->adminUser());

        $response = $this->put(route('admin.license-types.update', $licenseType), [
            'code' => 'D',
            'name' => 'Updated Name',
            'is_active' => false,
        ]);

        $response->assertRedirect(route('admin.license-types.index'));
        $this->assertDatabaseHas('license_types', ['id' => $licenseType->id, 'name' => 'Updated Name']);
    }

    public function test_admin_can_sync_categories()
    {
        $licenseType = LicenseType::factory()->create();
        $categories = Category::factory()->count(3)->create();
        $this->actingAs($this->adminUser());

        $this->post(route('admin.license-types.sync-categories', $licenseType), [
            'category_ids' => [$categories[0]->id, $categories[1]->id],
        ]);

        $this->assertEquals(2, $licenseType->categories()->count());
        $this->assertTrue($licenseType->categories()->where('id', $categories[0]->id)->exists());
    }

    public function test_admin_cannot_delete_license_type_with_quizzes()
    {
        $licenseType = LicenseType::factory()->create();
        Quiz::factory()->create(['license_type_id' => $licenseType->id]);

        $this->actingAs($this->adminUser());

        $response = $this->delete(route('admin.license-types.destroy', $licenseType));

        $response->assertSessionHas('error');
        $this->assertDatabaseHas('license_types', ['id' => $licenseType->id]);
    }

    public function test_admin_can_delete_license_type_without_quizzes()
    {
        $licenseType = LicenseType::factory()->create();
        $this->actingAs($this->adminUser());

        $response = $this->delete(route('admin.license-types.destroy', $licenseType));

        $response->assertSessionHas('success');
        $this->assertDatabaseMissing('license_types', ['id' => $licenseType->id]);
    }

    public function test_cascade_delete_removes_pivot_rows()
    {
        $licenseType = LicenseType::factory()->create();
        $category = Category::factory()->create();
        $licenseType->categories()->attach($category->id);

        $category->delete();

        $this->assertDatabaseMissing('category_license_type', [
            'category_id' => $category->id,
            'license_type_id' => $licenseType->id,
        ]);
    }

    public function test_license_types_table_exists()
    {
        $this->seed(LicenseTypeSeeder::class);

        $this->assertTrue(LicenseType::count() >= 17);
        $this->assertDatabaseHas('license_types', ['code' => 'B']);
    }
}
