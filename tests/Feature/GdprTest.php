<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\RegistrazioneApprovataNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class GdprTest extends TestCase
{
    use RefreshDatabase;

    private function makeViewerWithPii(): User
    {
        Storage::fake('public');

        $document = UploadedFile::fake()->create('cf.pdf', 50, 'application/pdf');
        $path = $document->store('registrations', 'public');

        return User::factory()->create([
            'role'                      => User::ROLE_VIEWER,
            'name'                      => 'Mario Rossi',
            'email'                     => 'mario.rossi@example.com',
            'first_name'                => 'Mario',
            'last_name'                 => 'Rossi',
            'address'                   => 'Via Roma 1, Milano',
            'birth_date'                => '1990-01-01',
            'birth_place'               => 'Milano',
            'fiscal_code'               => 'RSSMRA90A01F205Z',
            'id_document_path'          => $path,
            'registration_status'       => User::REG_APPROVED,
            'registration_submitted_at' => now(),
            'registration_reviewed_at'  => now(),
        ]);
    }

    public function test_anonymize_clears_all_pii_fields_and_deletes_document(): void
    {
        $viewer = $this->makeViewerWithPii();
        $document = $viewer->id_document_path;
        Storage::disk('public')->assertExists($document);

        // Una notifica nel DB, per verificare che venga eliminata
        $viewer->notify(new RegistrazioneApprovataNotification());
        $this->assertSame(1, $viewer->notifications()->count());

        $this->artisan('gdpr:anonymize', ['user_id' => $viewer->id])
            ->assertExitCode(0);

        $viewer->refresh();

        $this->assertSame("Utente Anonimo {$viewer->id}", $viewer->name);
        $this->assertSame("anonimo-{$viewer->id}@eliminato.invalid", $viewer->email);
        $this->assertNull($viewer->first_name);
        $this->assertNull($viewer->last_name);
        $this->assertNull($viewer->address);
        $this->assertNull($viewer->birth_date);
        $this->assertNull($viewer->birth_place);
        $this->assertNull($viewer->fiscal_code);
        $this->assertNull($viewer->id_document_path);
        $this->assertNull($viewer->email_verified_at);
        $this->assertNull($viewer->remember_token);
        $this->assertSame(User::REG_NONE, $viewer->registration_status);
        $this->assertNull($viewer->registration_submitted_at);
        $this->assertNull($viewer->registration_reviewed_at);

        Storage::disk('public')->assertMissing($document);
        $this->assertSame(0, $viewer->notifications()->count());
    }

    public function test_anonymize_blocks_admin_user(): void
    {
        $admin = User::factory()->create([
            'role'  => User::ROLE_ADMIN,
            'email' => 'admin@example.com',
            'name'  => 'Admin Boss',
        ]);

        $this->artisan('gdpr:anonymize', ['user_id' => $admin->id])
            ->assertExitCode(1);

        $admin->refresh();
        $this->assertSame('admin@example.com', $admin->email);
        $this->assertSame('Admin Boss', $admin->name);
    }

    public function test_anonymize_returns_failure_for_missing_user(): void
    {
        $this->artisan('gdpr:anonymize', ['user_id' => 999999])
            ->assertExitCode(1);
    }

    public function test_dry_run_does_not_modify_anything(): void
    {
        $viewer = $this->makeViewerWithPii();
        $document = $viewer->id_document_path;

        $viewer->notify(new RegistrazioneApprovataNotification());
        $notificationsBefore = $viewer->notifications()->count();

        $this->artisan('gdpr:anonymize', ['user_id' => $viewer->id, '--dry-run' => true])
            ->assertExitCode(0);

        $viewer->refresh();

        $this->assertSame('mario.rossi@example.com', $viewer->email);
        $this->assertSame('Mario', $viewer->first_name);
        $this->assertSame('RSSMRA90A01F205Z', $viewer->fiscal_code);
        $this->assertSame($document, $viewer->id_document_path);
        Storage::disk('public')->assertExists($document);
        $this->assertSame($notificationsBefore, $viewer->notifications()->count());
    }

    public function test_anonymized_user_cannot_login(): void
    {
        $viewer = User::factory()->create([
            'role'     => User::ROLE_VIEWER,
            'email'    => 'mario.rossi@example.com',
            'password' => Hash::make('password'),
        ]);

        $this->artisan('gdpr:anonymize', ['user_id' => $viewer->id])
            ->assertExitCode(0);

        // 1. La vecchia email non funziona (record rimappato)
        $this->assertFalse(auth()->attempt([
            'email'    => 'mario.rossi@example.com',
            'password' => 'password',
        ]));

        // 2. La nuova email anonima esiste ma la password originale non vale più
        $this->assertFalse(auth()->attempt([
            'email'    => "anonimo-{$viewer->id}@eliminato.invalid",
            'password' => 'password',
        ]));

        $this->assertGuest();
    }

    public function test_anonymize_terminates_database_sessions(): void
    {
        config(['session.driver' => 'database']);

        $viewer = $this->makeViewerWithPii();

        DB::table('sessions')->insert([
            'id'            => 'session-abc-123',
            'user_id'       => $viewer->id,
            'ip_address'    => '127.0.0.1',
            'user_agent'    => 'phpunit',
            'payload'       => base64_encode('payload'),
            'last_activity' => now()->timestamp,
        ]);

        $this->assertSame(1, DB::table('sessions')->where('user_id', $viewer->id)->count());

        $this->artisan('gdpr:anonymize', ['user_id' => $viewer->id])
            ->assertExitCode(0);

        $this->assertSame(0, DB::table('sessions')->where('user_id', $viewer->id)->count());
    }

    public function test_gdpr_list_marks_anonymized_users(): void
    {
        User::factory()->create([
            'role'  => User::ROLE_VIEWER,
            'name'  => 'Luigi Verdi',
            'email' => 'luigi@example.com',
        ]);

        User::factory()->create([
            'role'  => User::ROLE_VIEWER,
            'name'  => "Utente Anonimo 42",
            'email' => 'anonimo-42@eliminato.invalid',
        ]);

        $this->artisan('gdpr:list')
            ->assertExitCode(0)
            ->expectsOutputToContain('luigi@example.com')
            ->expectsOutputToContain('anonimo-42@eliminato.invalid');
    }

    public function test_gdpr_list_anonymized_flag_filters_only_anonymized_users(): void
    {
        User::factory()->create([
            'role'  => User::ROLE_VIEWER,
            'name'  => 'Luigi Verdi',
            'email' => 'luigi@example.com',
        ]);

        User::factory()->create([
            'role'  => User::ROLE_VIEWER,
            'name'  => 'Utente Anonimo 99',
            'email' => 'anonimo-99@eliminato.invalid',
        ]);

        // Con --anonymized compare solo il viewer anonimizzato
        $this->artisan('gdpr:list', ['--anonymized' => true])
            ->assertExitCode(0)
            ->expectsOutputToContain('anonimo-99@eliminato.invalid');

        // Senza flag compaiono entrambi
        $this->artisan('gdpr:list')
            ->assertExitCode(0)
            ->expectsOutputToContain('luigi@example.com')
            ->expectsOutputToContain('anonimo-99@eliminato.invalid');
    }

    public function test_gdpr_list_anonymized_flag_empty_state_when_none_anonymized(): void
    {
        User::factory()->create([
            'role'  => User::ROLE_VIEWER,
            'name'  => 'Mario Bianchi',
            'email' => 'mario@example.com',
        ]);

        $this->artisan('gdpr:list', ['--anonymized' => true])
            ->assertExitCode(0)
            ->expectsOutputToContain('Nessun viewer anonimizzato');
    }
}
