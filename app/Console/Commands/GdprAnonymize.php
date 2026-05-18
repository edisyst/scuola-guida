<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

class GdprAnonymize extends Command
{
    protected $signature = 'gdpr:anonymize {user_id : ID dell\'utente da anonimizzare} {--dry-run : Mostra cosa farebbe il comando senza modificare nulla}';

    protected $description = 'Anonimizza i dati personali (PII) di un utente viewer ai sensi del GDPR. L\'utente non potrà più accedere.';

    private const DOCUMENT_DISK = 'public';

    private const ANON_EMAIL_DOMAIN = '@eliminato.invalid';

    public function handle(): int
    {
        $userId = (int) $this->argument('user_id');
        $dryRun = (bool) $this->option('dry-run');

        $this->info("Ricerca utente ID {$userId}...");

        $user = User::find($userId);

        if (! $user) {
            $this->error("Utente con ID {$userId} non trovato.");

            return self::FAILURE;
        }

        $this->info("Utente trovato: {$user->email}");

        if ($user->isAdmin()) {
            $this->error('Impossibile anonimizzare un utente con ruolo admin. Operazione bloccata.');

            return self::FAILURE;
        }

        if ($this->isAlreadyAnonymized($user)) {
            $this->warn('Questo utente risulta già anonimizzato (email termina con ' . self::ANON_EMAIL_DOMAIN . ').');
        }

        $notificationsCount = $user->notifications()->count();
        $hasDocument = (bool) $user->id_document_path;
        $documentPath = $user->id_document_path;

        if ($dryRun) {
            return $this->renderDryRun($user, $notificationsCount, $hasDocument, $documentPath);
        }

        $this->info('Inizio anonimizzazione...');

        try {
            DB::transaction(function () use ($user, $documentPath, $hasDocument, $notificationsCount) {
                $this->anonymizeUserRecord($user);
                $this->line('  <info>✓</info> Dati account anonimizzati (users)');

                $this->line('  <info>✓</info> Profilo anagrafico anonimizzato (users)');

                if ($hasDocument) {
                    $this->deleteDocument($documentPath);
                    $this->line("  <info>✓</info> File documento eliminato: {$documentPath}");
                }

                $deletedNotifications = $user->notifications()->delete();
                $this->line("  <info>✓</info> Notifiche eliminate: {$deletedNotifications} record");

                $this->terminateSessions($user->id);
            });
        } catch (Throwable $e) {
            $this->error('Anonimizzazione fallita, rollback eseguito: ' . $e->getMessage());
            Log::error('GDPR anonymize fallita', [
                'user_id'    => $userId,
                'executor'   => $this->resolveExecutor(),
                'error'      => $e->getMessage(),
            ]);

            return self::FAILURE;
        }

        Log::info('GDPR anonymize eseguita', [
            'user_id'                 => $userId,
            'executor'                => $this->resolveExecutor(),
            'timestamp'               => now()->toIso8601String(),
            'notifications_deleted'   => $notificationsCount,
            'document_deleted'        => $hasDocument,
        ]);

        $this->info("Anonimizzazione completata. L'utente non può più accedere al sistema.");

        return self::SUCCESS;
    }

    private function renderDryRun(User $user, int $notificationsCount, bool $hasDocument, ?string $documentPath): int
    {
        $this->warn('[DRY RUN] Nessuna modifica verrà applicata.');
        $this->line("Utente trovato: {$user->email}");
        $this->line('');
        $this->line('Verrebbe anonimizzato:');
        $this->line('  users: name, email, password, first_name, last_name, address, birth_date, birth_place, fiscal_code, id_document_path, registration fields, remember_token');

        if ($hasDocument) {
            $this->line("  storage ({$documentPath}): file documento eliminato");
        } else {
            $this->line('  storage: nessun documento da eliminare');
        }

        $this->line("  notifications: {$notificationsCount} record eliminati");
        $this->line('  sessions: chiusura sessioni attive (driver: ' . config('session.driver') . ')');
        $this->warn('[DRY RUN] Fine simulazione.');

        return self::SUCCESS;
    }

    private function anonymizeUserRecord(User $user): void
    {
        $id = $user->id;

        // Bypass mutators/casts del modello: scriviamo direttamente con query builder
        // per evitare che il cast 'hashed' rihashi una password già hashata.
        DB::table('users')->where('id', $id)->update([
            'name'                          => "Utente Anonimo {$id}",
            'email'                         => "anonimo-{$id}" . self::ANON_EMAIL_DOMAIN,
            'email_verified_at'             => null,
            'password'                      => Hash::make(Str::random(64)),
            'remember_token'                => null,
            'first_name'                    => null,
            'last_name'                     => null,
            'address'                       => null,
            'birth_date'                    => null,
            'birth_place'                   => null,
            'fiscal_code'                   => null,
            'id_document_path'              => null,
            'registration_status'           => User::REG_NONE,
            'registration_submitted_at'     => null,
            'registration_reviewed_at'      => null,
            'registration_reviewed_by'      => null,
            'registration_rejection_reason' => null,
            'updated_at'                    => now(),
        ]);
    }

    private function deleteDocument(string $path): void
    {
        $disk = Storage::disk(self::DOCUMENT_DISK);

        if ($disk->exists($path)) {
            $disk->delete($path);
        }
    }

    private function terminateSessions(int $userId): void
    {
        $driver = config('session.driver');

        if ($driver === 'database') {
            $deleted = DB::table(config('session.table', 'sessions'))
                ->where('user_id', $userId)
                ->delete();
            $this->line("  <info>✓</info> Sessioni attive chiuse: {$deleted}");

            return;
        }

        // Driver non-database: le sessioni file/redis non sono indicizzate per
        // user_id quindi non possiamo chiuderle dal codice in modo affidabile.
        // L'operatore deve invalidarle manualmente (es. svuotare storage/framework/sessions).
        $this->warn("  Driver sessione '{$driver}': chiudi manualmente le sessioni attive dell'utente.");
    }

    private function isAlreadyAnonymized(User $user): bool
    {
        return str_ends_with((string) $user->email, self::ANON_EMAIL_DOMAIN);
    }

    private function resolveExecutor(): string
    {
        return get_current_user() ?: 'cli';
    }
}
