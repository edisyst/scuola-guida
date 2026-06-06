<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Question;
use App\Models\LicenseType;
use App\Models\QuestionTranslation;
use App\Models\User;
use App\Services\QuestionTranslationService;
use App\Services\StudyService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuestionTranslationTest extends TestCase
{
    use RefreshDatabase;

    private LicenseType $licenseType;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(\App\Http\Middleware\EnsureTwoFactorAuthenticated::class);
        $this->licenseType = LicenseType::factory()->create();
    }

    private function adminUser(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    private function editorUser(): User
    {
        // Editor con override esplicito del permesso (la tabella role_permissions
        // è vuota sotto RefreshDatabase).
        return User::factory()->create([
            'role'        => 'editor',
            'permissions' => ['edit_question'],
        ]);
    }

    private function viewerUser(?string $locale = null): User
    {
        return User::factory()->create([
            'role'                   => 'viewer',
            'locale'                 => $locale,
            'active_license_type_id' => $this->licenseType->id,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | MODEL — getLocalizedText
    |--------------------------------------------------------------------------
    */

    public function test_get_localized_text_returns_english_translation_when_present(): void
    {
        $question = Question::factory()->create(['question' => 'Testo originale']);
        QuestionTranslation::factory()->locale('en')->create([
            'question_id' => $question->id,
            'text'        => 'Original text',
        ]);

        $question->load('translations');

        $this->assertSame('Original text', $question->getLocalizedText('en'));
    }

    public function test_get_localized_text_falls_back_to_italian_when_translation_missing(): void
    {
        $question = Question::factory()->create(['question' => 'Testo originale']);
        QuestionTranslation::factory()->locale('en')->create([
            'question_id' => $question->id,
            'text'        => 'Original text',
        ]);

        $question->load('translations');

        // Nessuna traduzione tedesca => fallback al testo italiano.
        $this->assertSame('Testo originale', $question->getLocalizedText('de'));
    }

    public function test_get_localized_text_returns_original_for_default_locale(): void
    {
        $question = Question::factory()->create(['question' => 'Testo originale']);

        $this->assertSame('Testo originale', $question->getLocalizedText('it'));
    }

    /*
    |--------------------------------------------------------------------------
    | SERVICE — upsert idempotente / indice unico
    |--------------------------------------------------------------------------
    */

    public function test_upsert_is_idempotent_for_same_question_and_locale(): void
    {
        $service  = app(QuestionTranslationService::class);
        $question = Question::factory()->create();

        $service->upsert($question, 'en', 'First text');
        $service->upsert($question, 'en', 'Updated text');

        $this->assertSame(1, QuestionTranslation::where('question_id', $question->id)
            ->where('locale', 'en')->count());
        $this->assertDatabaseHas('question_translations', [
            'question_id' => $question->id,
            'locale'      => 'en',
            'text'        => 'Updated text',
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | CONTROLLER — autorizzazione
    |--------------------------------------------------------------------------
    */

    public function test_admin_can_create_translation(): void
    {
        $question = Question::factory()->create();

        $response = $this->actingAs($this->adminUser())
            ->withSession(['2fa_verified' => true])
            ->post(route('admin.questions.translations.store', $question), [
                'locale' => 'en',
                'text'   => 'Original text',
            ]);

        $response->assertRedirect(route('admin.questions.edit', $question));
        $this->assertDatabaseHas('question_translations', [
            'question_id' => $question->id,
            'locale'      => 'en',
            'text'        => 'Original text',
        ]);
    }

    public function test_editor_can_create_translation(): void
    {
        $question = Question::factory()->create();

        $this->actingAs($this->editorUser())
            ->withSession(['2fa_verified' => true])
            ->post(route('admin.questions.translations.store', $question), [
                'locale' => 'fr',
                'text'   => 'Texte original',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('question_translations', [
            'question_id' => $question->id,
            'locale'      => 'fr',
        ]);
    }

    public function test_viewer_cannot_create_translation(): void
    {
        $question = Question::factory()->create();

        $this->actingAs($this->viewerUser())
            ->withSession(['2fa_verified' => true])
            ->post(route('admin.questions.translations.store', $question), [
                'locale' => 'en',
                'text'   => 'Original text',
            ])
            ->assertForbidden();

        $this->assertDatabaseMissing('question_translations', [
            'question_id' => $question->id,
        ]);
    }

    public function test_created_by_is_set_from_authenticated_user(): void
    {
        $admin    = $this->adminUser();
        $question = Question::factory()->create();

        $this->actingAs($admin)
            ->withSession(['2fa_verified' => true])
            ->post(route('admin.questions.translations.store', $question), [
                'locale' => 'en',
                'text'   => 'Original text',
            ]);

        $this->assertDatabaseHas('question_translations', [
            'question_id' => $question->id,
            'created_by'  => $admin->id,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | STUDY VIEW — localizzazione
    |--------------------------------------------------------------------------
    */

    public function test_study_view_shows_preferred_locale_text(): void
    {
        $question = Question::factory()->create(['question' => 'Testo italiano univoco']);
        QuestionTranslation::factory()->locale('en')->create([
            'question_id' => $question->id,
            'text'        => 'EnglishUniqueText',
        ]);

        $viewer = $this->viewerUser('en');

        $response = $this->actingAs($viewer)
            ->withSession([
                StudyService::KEY_QUESTIONS => [$question->id],
                StudyService::KEY_INDEX     => 0,
            ])
            ->get(route('study.play'));

        $response->assertOk();
        $response->assertSee('EnglishUniqueText');
        $response->assertDontSee('Testo italiano univoco');
    }

    public function test_study_view_shows_italian_when_no_locale_set(): void
    {
        $question = Question::factory()->create(['question' => 'TestoItalianoUnivoco']);
        QuestionTranslation::factory()->locale('en')->create([
            'question_id' => $question->id,
            'text'        => 'EnglishUniqueText',
        ]);

        $viewer = $this->viewerUser(null);

        $response = $this->actingAs($viewer)
            ->withSession([
                StudyService::KEY_QUESTIONS => [$question->id],
                StudyService::KEY_INDEX     => 0,
            ])
            ->get(route('study.play'));

        $response->assertOk();
        $response->assertSee('TestoItalianoUnivoco');
        $response->assertDontSee('EnglishUniqueText');
    }

    /*
    |--------------------------------------------------------------------------
    | EDIT PAGE — sezione traduzioni integrata
    |--------------------------------------------------------------------------
    */

    public function test_edit_page_shows_existing_translations(): void
    {
        $question = Question::factory()->create(['question' => 'Testo italiano']);
        QuestionTranslation::factory()->locale('en')->create([
            'question_id' => $question->id,
            'text'        => 'English text',
        ]);

        $this->actingAs($this->adminUser())
            ->withSession(['2fa_verified' => true])
            ->get(route('admin.questions.edit', $question))
            ->assertOk()
            ->assertSee('English text');
    }

    public function test_edit_page_shows_add_translation_form_for_available_locales(): void
    {
        $question = Question::factory()->create();

        $this->actingAs($this->adminUser())
            ->withSession(['2fa_verified' => true])
            ->get(route('admin.questions.edit', $question))
            ->assertOk()
            ->assertSee('Aggiungi traduzione');
    }

    public function test_edit_page_hides_translation_form_for_viewer(): void
    {
        $question = Question::factory()->create();

        $this->actingAs($this->viewerUser())
            ->withSession(['2fa_verified' => true])
            ->get(route('admin.questions.edit', $question))
            ->assertOk()
            ->assertDontSee('Aggiungi traduzione');
    }

    /*
    |--------------------------------------------------------------------------
    | CASCADE + PROFILO
    |--------------------------------------------------------------------------
    */

    public function test_deleting_question_cascades_to_translations(): void
    {
        $question = Question::factory()->create();
        QuestionTranslation::factory()->locale('en')->create(['question_id' => $question->id]);

        $question->delete();

        $this->assertDatabaseMissing('question_translations', [
            'question_id' => $question->id,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | BANDIERINA — LocaleController persiste users.locale
    |--------------------------------------------------------------------------
    */

    public function test_flag_switcher_persists_locale_to_users_locale(): void
    {
        $viewer = $this->viewerUser(null);

        $this->actingAs($viewer)
            ->post(route('locale.switch'), ['locale' => 'en'])
            ->assertRedirect();

        $this->assertSame('en', $viewer->fresh()->locale);
    }

    public function test_flag_switcher_sets_session_app_locale(): void
    {
        $viewer = $this->viewerUser(null);

        $this->actingAs($viewer)
            ->post(route('locale.switch'), ['locale' => 'en']);

        $this->assertEquals('en', session('app_locale'));
    }

    public function test_flag_switcher_unauthenticated_does_not_crash(): void
    {
        $this->post(route('locale.switch'), ['locale' => 'en'])
            ->assertRedirect();
    }

    public function test_invalid_locale_is_rejected(): void
    {
        $question = Question::factory()->create();

        $this->actingAs($this->adminUser())
            ->withSession(['2fa_verified' => true])
            ->post(route('admin.questions.translations.store', $question), [
                'locale' => 'zz',
                'text'   => 'Whatever',
            ])
            ->assertSessionHasErrors('locale');
    }
}
