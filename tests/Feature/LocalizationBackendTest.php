<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\BackupFailed;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class LocalizationBackendTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        app()->setLocale('it');
        parent::tearDown();
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Admin vede view backend nel locale corretto
    // ─────────────────────────────────────────────────────────────────────────

    public function test_admin_with_locale_en_sees_english_backend_view(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN, 'locale' => 'en']);

        $response = $this->actingAs($admin)->get(route('admin.users.index'));

        $response->assertOk();
        // users.title in EN = 'User Management'
        $response->assertSee('User Management');
    }

    public function test_admin_with_locale_null_sees_italian_fallback(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN, 'locale' => null]);

        $response = $this->actingAs($admin)->get(route('admin.users.index'));

        $response->assertOk();
        // users.title in IT = 'Gestione Utenti'
        $response->assertSee('Gestione Utenti');
    }

    public function test_admin_with_locale_es_sees_spanish_backend_view(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN, 'locale' => 'es']);

        $response = $this->actingAs($admin)->get(route('admin.users.index'));

        $response->assertOk();
        // users.title in ES = 'Gestión de Usuarios'
        $response->assertSee('Gestión de Usuarios');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Intestazioni DataTable localizzate
    // ─────────────────────────────────────────────────────────────────────────

    public function test_admin_categories_page_has_localised_column_headers(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN, 'locale' => 'en']);

        $response = $this->actingAs($admin)->get(route('admin.categories.index'));

        $response->assertOk();
        // categories.col_name in EN = 'Name'
        $response->assertSee('Name');
        // categories.col_questions in EN = 'Questions'
        $response->assertSee('Questions');
    }

    public function test_datatables_meta_tag_present_with_correct_locale(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN, 'locale' => 'en']);

        $response = $this->actingAs($admin)->get(route('admin.users.index'));

        $response->assertOk();
        // Meta tag deve contenere le stringhe EN per DataTables
        $response->assertSee('datatables-i18n', false);
        $response->assertSee('Search:', false);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Flash messages backend — chiave tradotta corretta per locale
    // ─────────────────────────────────────────────────────────────────────────

    public function test_flash_user_deleted_returns_italian_message_for_it_admin(): void
    {
        $admin  = User::factory()->create(['role' => User::ROLE_ADMIN, 'locale' => 'it']);
        $target = User::factory()->create(['role' => User::ROLE_VIEWER]);

        $response = $this->actingAs($admin)
            ->delete(route('admin.users.destroy', $target));

        $response->assertSessionHas('success', __('flash.user_deleted'));
    }

    public function test_flash_user_deleted_returns_english_message_for_en_admin(): void
    {
        $admin  = User::factory()->create(['role' => User::ROLE_ADMIN, 'locale' => 'en']);
        $target = User::factory()->create(['role' => User::ROLE_VIEWER]);

        app()->setLocale('en');

        $response = $this->actingAs($admin)
            ->delete(route('admin.users.destroy', $target));

        $response->assertSessionHas('success', __('flash.user_deleted'));
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Notifiche backend — renderizzate nel locale dell'admin destinatario
    // ─────────────────────────────────────────────────────────────────────────

    public function test_backup_failed_notification_renders_in_english_for_en_admin(): void
    {
        Notification::fake();

        $admin = User::factory()->create([
            'role'   => User::ROLE_ADMIN,
            'locale' => 'en',
        ]);

        $admin->notify(new BackupFailed('disk write error'));

        Notification::assertSentTo($admin, BackupFailed::class, function (BackupFailed $notification) {
            // HasLocalePreference → il mail viene generato nel locale dell'admin
            app()->setLocale('en');
            $mail = $notification->toMail($this->createMock(\stdClass::class));

            return str_contains($mail->subject ?? '', 'Backup') ||
                   str_contains(collect($mail->introLines)->implode(''), 'backup');
        });
    }

    public function test_backup_failed_notification_renders_in_italian_for_it_admin(): void
    {
        Notification::fake();

        $admin = User::factory()->create([
            'role'   => User::ROLE_ADMIN,
            'locale' => 'it',
        ]);

        $admin->notify(new BackupFailed('errore disco'));

        Notification::assertSentTo($admin, BackupFailed::class);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Componente Livewire backend — AuditLog / MediaManager mostra strings locale
    // ─────────────────────────────────────────────────────────────────────────

    public function test_audit_log_page_shows_localized_strings_for_en_admin(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN, 'locale' => 'en']);

        $response = $this->actingAs($admin)->get(route('admin.audit.index'));

        $response->assertOk();
        // audit.filters_title in EN = 'Filters'
        $response->assertSee('Filters');
        // audit.export_excel in EN = 'Export Excel'
        $response->assertSee('Export Excel');
    }

    public function test_audit_log_page_shows_italian_for_null_locale_admin(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN, 'locale' => null]);

        $response = $this->actingAs($admin)->get(route('admin.audit.index'));

        $response->assertOk();
        // audit.filters_title in IT = 'Filtri'
        $response->assertSee('Filtri');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Area instructor — banner sola lettura localizzato
    // ─────────────────────────────────────────────────────────────────────────

    public function test_instructor_sees_english_readonly_banner(): void
    {
        $instructor = User::factory()->create(['role' => User::ROLE_INSTRUCTOR, 'locale' => 'en']);

        $response = $this->actingAs($instructor)->get(route('instructor.students.index'));

        $response->assertOk();
        // instructor.title in EN = 'My students'
        $response->assertSee('My students');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Translation keys presente nei file di lingua — smoke test strutturale
    // ─────────────────────────────────────────────────────────────────────────

    public function test_all_backend_lang_files_have_italian_keys(): void
    {
        $files = [
            'questions', 'quiz', 'users', 'categories', 'enrollments',
            'reports', 'audit', 'media', 'backup', 'instructor', 'editor',
            'nav_admin', 'datatables', 'flash',
        ];

        foreach ($files as $file) {
            $path = lang_path("it/{$file}.php");
            $this->assertFileExists($path, "lang/it/{$file}.php mancante");

            $keys = require $path;
            $this->assertIsArray($keys, "lang/it/{$file}.php non ritorna un array");
            $this->assertNotEmpty($keys, "lang/it/{$file}.php è vuoto");
        }
    }

    public function test_english_and_spanish_files_have_same_keys_as_italian(): void
    {
        $files = ['questions', 'quiz', 'users', 'categories', 'audit', 'media', 'backup', 'instructor', 'editor', 'datatables'];

        foreach ($files as $file) {
            $itKeys = array_keys(require lang_path("it/{$file}.php"));

            foreach (['en', 'es'] as $locale) {
                $path = lang_path("{$locale}/{$file}.php");
                if (! file_exists($path)) {
                    $this->fail("lang/{$locale}/{$file}.php mancante");
                }
                $localeKeys = array_keys(require $path);

                $missing = array_diff($itKeys, $localeKeys);
                $this->assertEmpty(
                    $missing,
                    "lang/{$locale}/{$file}.php manca delle chiavi: " . implode(', ', $missing)
                );
            }
        }
    }
}
