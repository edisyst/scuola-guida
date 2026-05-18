<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class GdprList extends Command
{
    protected $signature = 'gdpr:list';

    protected $description = 'Elenca tutti i viewer con visibilità sullo stato di anonimizzazione (GDPR).';

    private const ANON_EMAIL_DOMAIN = '@eliminato.invalid';

    public function handle(): int
    {
        $viewers = User::query()
            ->where('role', User::ROLE_VIEWER)
            ->withCount('quizAttempts')
            ->orderBy('id')
            ->get();

        if ($viewers->isEmpty()) {
            $this->warn('Nessun viewer presente nel sistema.');

            return self::SUCCESS;
        }

        $rows = $viewers->map(fn (User $u) => [
            'id'             => $u->id,
            'nome'           => $u->name,
            'email'          => $u->email,
            'iscritto_il'    => optional($u->created_at)->format('Y-m-d') ?? '—',
            'tentativi_quiz' => $u->quiz_attempts_count,
            'anonimizzato'   => str_ends_with((string) $u->email, self::ANON_EMAIL_DOMAIN) ? 'Sì' : 'No',
        ])->all();

        $this->table(
            ['ID', 'Nome', 'Email', 'Iscritto il', 'Tentativi quiz', 'Anonimizzato'],
            $rows,
        );

        return self::SUCCESS;
    }
}
