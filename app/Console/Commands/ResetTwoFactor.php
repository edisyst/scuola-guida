<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ResetTwoFactor extends Command
{
    protected $signature = '2fa:reset {user_id : ID dell\'utente a cui resettare il 2FA}';

    protected $description = 'Disabilita il 2FA per un utente (utile se perde il dispositivo e tutti i codici di recupero).';

    public function handle(): int
    {
        $userId = (int) $this->argument('user_id');

        $user = User::find($userId);

        if (! $user) {
            $this->error("Utente con ID {$userId} non trovato.");
            return self::FAILURE;
        }

        if (! $user->hasTwoFactorEnabled()) {
            $this->warn("L'utente {$user->email} non ha il 2FA abilitato.");
            return self::SUCCESS;
        }

        $user->two_factor_secret = null;
        $user->two_factor_enabled_at = null;
        $user->two_factor_recovery_codes = null;
        $user->save();

        Log::info('2FA reset via CLI', [
            'user_id'   => $userId,
            'email'     => $user->email,
            'executor'  => get_current_user() ?: 'cli',
            'timestamp' => now()->toIso8601String(),
        ]);

        $this->info("2FA disabilitato per l'utente {$user->email} (ID {$userId}).");

        return self::SUCCESS;
    }
}
