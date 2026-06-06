<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\CategoryMaterial;
use App\Models\LicenseType;
use App\Models\Question;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CategoryMaterialTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(\App\Http\Middleware\EnsureTwoFactorAuthenticated::class);
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    private function editor(): User
    {
        return User::factory()->create(['role' => 'editor']);
    }

    private function viewer(): User
    {
        $licenseType = LicenseType::factory()->create(['is_active' => true]);
        return User::factory()->create([
            'role' => 'viewer',
            'active_license_type_id' => $licenseType->id,
        ]);
    }

    // ── Admin/editor can create each type ────────────────────────────────

    public function test_admin_can_create_note_material(): void
    {
        $category = Category::factory()->create();

        $response = $this->actingAs($this->admin())
            ->post(route('admin.categories.materials.store', $category), [
                'type'    => 'note',
                'title'   => 'Teoria segnali',
                'content' => 'Contenuto della nota',
            ]);

        $response->assertRedirect(route('admin.categories.materials.index', $category));
        $this->assertDatabaseHas('category_materials', [
            'category_id' => $category->id,
            'type'        => 'note',
            'title'       => 'Teoria segnali',
        ]);
    }

    public function test_admin_can_create_link_material(): void
    {
        $category = Category::factory()->create();

        $response = $this->actingAs($this->admin())
            ->post(route('admin.categories.materials.store', $category), [
                'type'        => 'link',
                'title'       => 'Video YouTube',
                'url_or_path' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
            ]);

        $response->assertRedirect(route('admin.categories.materials.index', $category));
        $this->assertDatabaseHas('category_materials', [
            'category_id' => $category->id,
            'type'        => 'link',
            'url_or_path' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
        ]);
    }

    public function test_admin_can_create_pdf_material(): void
    {
        Storage::fake('public');

        $category = Category::factory()->create();
        $file     = UploadedFile::fake()->create('dispensa.pdf', 512, 'application/pdf');

        $response = $this->actingAs($this->admin())
            ->post(route('admin.categories.materials.store', $category), [
                'type'  => 'pdf',
                'title' => 'Dispensa PDF',
                'file'  => $file,
            ]);

        $response->assertRedirect(route('admin.categories.materials.index', $category));

        $material = CategoryMaterial::where('category_id', $category->id)->first();
        $this->assertNotNull($material);
        $this->assertEquals('pdf', $material->type);
        Storage::disk('public')->assertExists($material->url_or_path);
    }

    // ── Viewer cannot access admin management routes ──────────────────────

    public function test_viewer_cannot_access_materials_index(): void
    {
        $category = Category::factory()->create();

        $this->actingAs($this->viewer())
            ->get(route('admin.categories.materials.index', $category))
            ->assertStatus(403);
    }

    public function test_viewer_cannot_store_material(): void
    {
        $category = Category::factory()->create();

        $this->actingAs($this->viewer())
            ->post(route('admin.categories.materials.store', $category), [
                'type'    => 'note',
                'title'   => 'Test',
                'content' => 'Test',
            ])
            ->assertStatus(403);
    }

    // ── Deleting category cascades to materials ────────────────────────────

    public function test_deleting_category_deletes_its_materials(): void
    {
        $category = Category::factory()->create();
        CategoryMaterial::factory()->count(3)->create(['category_id' => $category->id]);

        $this->assertDatabaseCount('category_materials', 3);

        $category->delete();

        $this->assertDatabaseCount('category_materials', 0);
    }

    // ── Deleting pdf material removes the physical file ──────────────────

    public function test_deleting_pdf_material_removes_physical_file(): void
    {
        Storage::fake('public');

        $category = Category::factory()->create();
        $file     = UploadedFile::fake()->create('doc.pdf', 100, 'application/pdf');

        $this->actingAs($this->admin())
            ->post(route('admin.categories.materials.store', $category), [
                'type'  => 'pdf',
                'title' => 'Documento',
                'file'  => $file,
            ]);

        $material = CategoryMaterial::where('category_id', $category->id)->first();
        Storage::disk('public')->assertExists($material->url_or_path);

        $this->actingAs($this->admin())
            ->delete(route('admin.categories.materials.destroy', [$category, $material]));

        Storage::disk('public')->assertMissing($material->url_or_path);
        $this->assertDatabaseMissing('category_materials', ['id' => $material->id]);
    }

    // ── Viewer sees materials on study page ───────────────────────────────

    public function test_viewer_sees_material_block_on_study_page(): void
    {
        $category = Category::factory()->create();
        $question = Question::factory()->create(['category_id' => $category->id]);
        CategoryMaterial::factory()->create([
            'category_id' => $category->id,
            'type'        => 'note',
            'title'       => 'Appunti importanti',
            'content'     => 'Contenuto di prova',
        ]);

        // Seed study session manually
        $viewer = $this->viewer();
        session(['study_questions' => [$question->id], 'study_index' => 0]);

        $response = $this->actingAs($viewer)
            ->get(route('study.play'));

        $response->assertStatus(200);
        $response->assertSee('Materiale didattico');
        $response->assertSee('Appunti importanti');
    }

    // ── Validation: PDF rejected for non-PDF file ─────────────────────────

    public function test_pdf_type_requires_pdf_file(): void
    {
        Storage::fake('public');

        $category = Category::factory()->create();
        $notPdf   = UploadedFile::fake()->image('image.jpg');

        $response = $this->actingAs($this->admin())
            ->post(route('admin.categories.materials.store', $category), [
                'type'  => 'pdf',
                'title' => 'Test',
                'file'  => $notPdf,
            ]);

        $response->assertSessionHasErrors('file');
    }

    // ── Validation: link rejected for non-URL string ──────────────────────

    public function test_link_type_requires_valid_url(): void
    {
        $category = Category::factory()->create();

        $response = $this->actingAs($this->admin())
            ->post(route('admin.categories.materials.store', $category), [
                'type'        => 'link',
                'title'       => 'Test',
                'url_or_path' => 'not-a-url',
            ]);

        $response->assertSessionHasErrors('url_or_path');
    }

    // ── Accessor: YouTube embed URL ───────────────────────────────────────

    public function test_youtube_watch_url_produces_embed_url(): void
    {
        $material = CategoryMaterial::factory()->youtube()->make();

        $this->assertStringContainsString('youtube.com/embed/', $material->embed_url);
    }

    public function test_non_youtube_url_returns_null_embed(): void
    {
        $material = CategoryMaterial::factory()->link()->make();

        $this->assertNull($material->embed_url);
    }

    // ── Reorder ───────────────────────────────────────────────────────────

    public function test_admin_can_reorder_materials(): void
    {
        $category = Category::factory()->create();
        $m1 = CategoryMaterial::factory()->create(['category_id' => $category->id, 'position' => 0]);
        $m2 = CategoryMaterial::factory()->create(['category_id' => $category->id, 'position' => 1]);

        $this->actingAs($this->admin())
            ->post(route('admin.categories.materials.reorder', $category), [
                'ids' => [$m2->id, $m1->id],
            ])
            ->assertJson(['ok' => true]);

        $this->assertEquals(0, $m2->fresh()->position);
        $this->assertEquals(1, $m1->fresh()->position);
    }
}
