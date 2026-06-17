<?php

namespace Tests\Feature;

use App\Http\Livewire\Admin\FormFieldsManager;
use App\Http\Middleware\EnsureTwoFactorAuthenticated;
use App\Models\SystemSetting;
use App\Models\User;
use App\Services\FormFieldService;
use App\Services\SettingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Redis;
use Livewire\Livewire;
use Tests\TestCase;

class FormFieldsConfigTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(EnsureTwoFactorAuthenticated::class);

        // Bypass Redis cache so tests always read from DB
        Redis::shouldReceive('get')->andReturn(null);
        Redis::shouldReceive('setex')->andReturn(true);
        Redis::shouldReceive('del')->andReturn(1);
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Admin può salvare la configurazione dei campi
    // ──────────────────────────────────────────────────────────────────────────

    public function test_admin_can_save_form_fields_configuration(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin);

        // Seed defaults so the migration settings exist
        $this->seedDefaults();

        Livewire::test(FormFieldsManager::class)
            ->call('toggle', 'registration', 0, 'enabled')
            ->call('save')
            ->assertHasNoErrors();

        $saved = json_decode(
            SystemSetting::where('key', 'forms.registration_fields')->value('value'),
            true
        );

        $this->assertTrue($saved[0]['enabled']);
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Campo enrollment required → validazione fallisce se mancante
    // ──────────────────────────────────────────────────────────────────────────

    public function test_required_enrollment_field_fails_validation_when_missing(): void
    {
        $this->seedDefaults();

        $viewer = User::factory()->create([
            'role'                => 'viewer',
            'registration_status' => 'none',
        ]);

        $this->actingAs($viewer);

        $response = $this->post(route('profile.registration.submit'), [
            // Deliberately omit 'first_name' which is required by default
            'last_name'   => 'Rossi',
            'address'     => 'Via Roma 1',
            'birth_date'  => '1990-01-01',
            'birth_place' => 'Roma',
            'fiscal_code' => 'RSSMRA90A01H501Z',
        ]);

        $response->assertSessionHasErrors('first_name');
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Campo enrollment disattivato → non viene validato né salvato
    // ──────────────────────────────────────────────────────────────────────────

    public function test_disabled_enrollment_field_is_not_validated(): void
    {
        // Disable 'address'
        $this->seedWith(enrollmentOverrides: ['address' => ['enabled' => false, 'required' => false]]);

        $service = app(FormFieldService::class);
        $rules = $service->validationRules('enrollment');

        $this->assertArrayNotHasKey('address', $rules);
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Campi core registrazione restano obbligatori anche se config tenta di
    // rimuoverli (StoreRegistrationRequest ha le regole hardcodate per name/email/password)
    // ──────────────────────────────────────────────────────────────────────────

    public function test_core_registration_fields_remain_required_regardless_of_config(): void
    {
        $this->seedDefaults();

        // Attempt to register without 'name' (core field)
        $response = $this->post('/register', [
            'email'                 => 'test@example.com',
            'password'              => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]);

        $response->assertSessionHasErrors('name');
    }

    // ──────────────────────────────────────────────────────────────────────────
    // FormFieldService::validationRules genera le regole correttamente
    // ──────────────────────────────────────────────────────────────────────────

    public function test_validation_rules_include_only_enabled_fields(): void
    {
        $this->seedWith(enrollmentOverrides: [
            'address'    => ['enabled' => false, 'required' => false],
            'birth_date' => ['enabled' => true, 'required' => false],
        ]);

        $rules = app(FormFieldService::class)->validationRules('enrollment');

        $this->assertArrayNotHasKey('address', $rules);
        $this->assertArrayHasKey('birth_date', $rules);
        $this->assertContains('nullable', $rules['birth_date']);
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Admin page renderable
    // ──────────────────────────────────────────────────────────────────────────

    public function test_admin_can_access_form_fields_page(): void
    {
        $this->seedDefaults();
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->get(route('admin.system.form-fields'))
            ->assertOk();
    }

    public function test_non_admin_cannot_access_form_fields_page(): void
    {
        $editor = User::factory()->create(['role' => 'editor']);

        $this->actingAs($editor)
            ->get(route('admin.system.form-fields'))
            ->assertForbidden();
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Helpers
    // ──────────────────────────────────────────────────────────────────────────

    private function seedDefaults(): void
    {
        $this->artisan('migrate', ['--path' => 'database/migrations/2026_06_17_214357_seed_forms_settings.php']);
    }

    private function seedWith(array $enrollmentOverrides = [], array $registrationOverrides = []): void
    {
        $this->seedDefaults();

        $enrollFields = json_decode(
            SystemSetting::where('key', 'forms.enrollment_fields')->value('value') ?? '[]',
            true
        ) ?: [];

        foreach ($enrollFields as &$field) {
            if (isset($enrollmentOverrides[$field['key']])) {
                $field = array_merge($field, $enrollmentOverrides[$field['key']]);
            }
        }
        unset($field);

        $regFields = json_decode(
            SystemSetting::where('key', 'forms.registration_fields')->value('value') ?? '[]',
            true
        ) ?: [];

        foreach ($regFields as &$field) {
            if (isset($registrationOverrides[$field['key']])) {
                $field = array_merge($field, $registrationOverrides[$field['key']]);
            }
        }
        unset($field);

        app(SettingService::class)->setMany([
            'forms.enrollment_fields'   => json_encode($enrollFields),
            'forms.registration_fields' => json_encode($regFields),
        ]);
    }
}
