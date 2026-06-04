<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\CategoryTranslation;
use App\Models\User;
use App\Services\CategoryTranslationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryTranslationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(\App\Http\Middleware\EnsureTwoFactorAuthenticated::class);
    }

    private function adminUser(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    private function editorUser(): User
    {
        return User::factory()->create([
            'role'        => 'editor',
            'permissions' => ['edit_category'],
        ]);
    }

    private function viewerUser(): User
    {
        return User::factory()->create(['role' => 'viewer']);
    }

    /*
    |--------------------------------------------------------------------------
    | MODEL — getLocalizedName
    |--------------------------------------------------------------------------
    */

    public function test_get_localized_name_returns_english_translation_when_present(): void
    {
        $category = Category::factory()->create(['name' => 'Segnali di pericolo']);
        CategoryTranslation::factory()->locale('en')->create([
            'category_id' => $category->id,
            'name'        => 'Danger signs',
        ]);

        $category->load('translations');

        $this->assertSame('Danger signs', $category->getLocalizedName('en'));
    }

    public function test_get_localized_name_falls_back_to_italian_when_translation_missing(): void
    {
        $category = Category::factory()->create(['name' => 'Segnali di pericolo']);
        CategoryTranslation::factory()->locale('en')->create([
            'category_id' => $category->id,
            'name'        => 'Danger signs',
        ]);

        $category->load('translations');

        $this->assertSame('Segnali di pericolo', $category->getLocalizedName('de'));
    }

    public function test_get_localized_name_returns_original_for_italian_locale(): void
    {
        $category = Category::factory()->create(['name' => 'Segnali di pericolo']);

        $this->assertSame('Segnali di pericolo', $category->getLocalizedName('it'));
    }

    /*
    |--------------------------------------------------------------------------
    | SERVICE — upsert idempotente / indice unico
    |--------------------------------------------------------------------------
    */

    public function test_upsert_is_idempotent_for_same_category_and_locale(): void
    {
        $service  = app(CategoryTranslationService::class);
        $category = Category::factory()->create();

        $service->upsert($category, 'en', 'First name');
        $service->upsert($category, 'en', 'Updated name');

        $this->assertSame(1, CategoryTranslation::where('category_id', $category->id)
            ->where('locale', 'en')->count());
        $this->assertDatabaseHas('category_translations', [
            'category_id' => $category->id,
            'locale'      => 'en',
            'name'        => 'Updated name',
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | CONTROLLER — autorizzazione
    |--------------------------------------------------------------------------
    */

    public function test_admin_can_create_translation(): void
    {
        $category = Category::factory()->create();

        $this->actingAs($this->adminUser())
            ->withSession(['2fa_verified' => true])
            ->post(route('admin.categories.translations.store', $category), [
                'locale' => 'en',
                'name'   => 'Danger signs',
            ])
            ->assertRedirect(route('admin.categories.edit', $category));

        $this->assertDatabaseHas('category_translations', [
            'category_id' => $category->id,
            'locale'      => 'en',
            'name'        => 'Danger signs',
        ]);
    }

    public function test_editor_can_create_translation(): void
    {
        $category = Category::factory()->create();

        $this->actingAs($this->editorUser())
            ->withSession(['2fa_verified' => true])
            ->post(route('admin.categories.translations.store', $category), [
                'locale' => 'en',
                'name'   => 'Danger signs',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('category_translations', [
            'category_id' => $category->id,
            'locale'      => 'en',
        ]);
    }

    public function test_viewer_cannot_create_translation(): void
    {
        $category = Category::factory()->create();

        $this->actingAs($this->viewerUser())
            ->withSession(['2fa_verified' => true])
            ->post(route('admin.categories.translations.store', $category), [
                'locale' => 'en',
                'name'   => 'Danger signs',
            ])
            ->assertForbidden();

        $this->assertDatabaseMissing('category_translations', [
            'category_id' => $category->id,
        ]);
    }

    public function test_created_by_is_set_from_authenticated_user(): void
    {
        $admin    = $this->adminUser();
        $category = Category::factory()->create();

        $this->actingAs($admin)
            ->withSession(['2fa_verified' => true])
            ->post(route('admin.categories.translations.store', $category), [
                'locale' => 'en',
                'name'   => 'Danger signs',
            ]);

        $this->assertDatabaseHas('category_translations', [
            'category_id' => $category->id,
            'created_by'  => $admin->id,
        ]);
    }

    public function test_admin_can_update_translation(): void
    {
        $category = Category::factory()->create();
        CategoryTranslation::factory()->locale('en')->create([
            'category_id' => $category->id,
            'name'        => 'Old name',
        ]);

        $this->actingAs($this->adminUser())
            ->withSession(['2fa_verified' => true])
            ->put(route('admin.categories.translations.update', [$category, 'en']), [
                'name' => 'New name',
            ])
            ->assertRedirect(route('admin.categories.edit', $category));

        $this->assertDatabaseHas('category_translations', [
            'category_id' => $category->id,
            'locale'      => 'en',
            'name'        => 'New name',
        ]);
    }

    public function test_admin_can_delete_translation(): void
    {
        $category = Category::factory()->create();
        CategoryTranslation::factory()->locale('en')->create([
            'category_id' => $category->id,
        ]);

        $this->actingAs($this->adminUser())
            ->withSession(['2fa_verified' => true])
            ->delete(route('admin.categories.translations.destroy', [$category, 'en']))
            ->assertRedirect(route('admin.categories.edit', $category));

        $this->assertDatabaseMissing('category_translations', [
            'category_id' => $category->id,
            'locale'      => 'en',
        ]);
    }

    public function test_invalid_locale_is_rejected(): void
    {
        $category = Category::factory()->create();

        $this->actingAs($this->adminUser())
            ->withSession(['2fa_verified' => true])
            ->post(route('admin.categories.translations.store', $category), [
                'locale' => 'zz',
                'name'   => 'Whatever',
            ])
            ->assertSessionHasErrors('locale');
    }

    public function test_default_locale_cannot_be_translated(): void
    {
        $category = Category::factory()->create();

        $this->actingAs($this->adminUser())
            ->withSession(['2fa_verified' => true])
            ->post(route('admin.categories.translations.store', $category), [
                'locale' => 'it',
                'name'   => 'Nome italiano',
            ])
            ->assertSessionHasErrors('locale');
    }

    /*
    |--------------------------------------------------------------------------
    | EDIT PAGE — sezione traduzioni visibile
    |--------------------------------------------------------------------------
    */

    public function test_edit_page_shows_translation_section(): void
    {
        $category = Category::factory()->create(['name' => 'Segnali di pericolo']);
        CategoryTranslation::factory()->locale('en')->create([
            'category_id' => $category->id,
            'name'        => 'Danger signs',
        ]);

        $this->actingAs($this->adminUser())
            ->withSession(['2fa_verified' => true])
            ->get(route('admin.categories.edit', $category))
            ->assertOk()
            ->assertSee('Traduzioni')
            ->assertSee('Danger signs');
    }

    public function test_edit_page_shows_add_form_when_no_translations(): void
    {
        $category = Category::factory()->create();

        $this->actingAs($this->adminUser())
            ->withSession(['2fa_verified' => true])
            ->get(route('admin.categories.edit', $category))
            ->assertOk()
            ->assertSee('Aggiungi traduzione');
    }

    /*
    |--------------------------------------------------------------------------
    | CASCADE
    |--------------------------------------------------------------------------
    */

    public function test_deleting_category_cascades_to_translations(): void
    {
        $category = Category::factory()->create();
        CategoryTranslation::factory()->locale('en')->create(['category_id' => $category->id]);

        $category->delete();

        $this->assertDatabaseMissing('category_translations', [
            'category_id' => $category->id,
        ]);
    }
}
