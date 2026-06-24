<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class NotificationSeeder extends Seeder
{
    // Notifiche demo per viewer (badge, esito iscrizione, risultato simulatore)
    private const VIEWER_NOTIFICATIONS = [
        [
            'type'  => 'App\\Notifications\\BadgeEarned',
            'data'  => [
                'title' => 'Badge sbloccato: Costanza',
                'body'  => '7 giorni consecutivi di studio — ottimo ritmo!',
                'url'   => '/profile/badges',
                'icon'  => 'fas fa-fire',
                'color' => 'warning',
            ],
        ],
        [
            'type'  => 'App\\Notifications\\BadgeEarned',
            'data'  => [
                'title' => 'Badge sbloccato: Centurione',
                'body'  => '100 domande risposte — grande impegno!',
                'url'   => '/profile/badges',
                'icon'  => 'fas fa-medal',
                'color' => 'info',
            ],
        ],
        [
            'type'  => 'App\\Notifications\\RegistrazioneApprovataNotification',
            'data'  => [
                'title' => 'Iscrizione approvata',
                'body'  => 'I tuoi dati anagrafici sono stati verificati. Puoi ora iscriverti agli esami ufficiali.',
                'url'   => '/quiz/confirmed',
                'icon'  => 'fas fa-check-circle',
                'color' => 'success',
            ],
        ],
        [
            'type'  => 'App\\Notifications\\SpacedRepetitionReminderNotification',
            'data'  => [
                'title' => 'Ripasso intelligente',
                'body'  => 'Hai 12 domande da ripassare oggi. Affronta la sessione per consolidare la memoria.',
                'url'   => '/smart-review',
                'icon'  => 'fas fa-brain',
                'color' => 'primary',
            ],
        ],
        [
            'type'  => 'App\\Notifications\\IscrizioneQuizApprovataNotification',
            'data'  => [
                'title' => 'Iscrizione all\'esame confermata',
                'body'  => 'La tua iscrizione all\'esame è stata confermata. Buona fortuna!',
                'url'   => '/quiz/enrollments',
                'icon'  => 'fas fa-clipboard-check',
                'color' => 'success',
            ],
        ],
    ];

    // Notifiche demo per admin/editor
    private const ADMIN_NOTIFICATIONS = [
        [
            'type'  => 'App\\Notifications\\NuovaRichiestaAnagraficaNotification',
            'data'  => [
                'title' => 'Nuova richiesta anagrafica',
                'body'  => 'Marco Rossi ha inviato i dati anagrafici per la verifica.',
                'url'   => '/admin/registrations',
                'icon'  => 'fas fa-user-plus',
                'color' => 'info',
            ],
        ],
        [
            'type'  => 'App\\Notifications\\NuovaRichiestaAnagraficaNotification',
            'data'  => [
                'title' => 'Nuova richiesta anagrafica',
                'body'  => 'Giulia Verdi ha inviato i dati anagrafici per la verifica.',
                'url'   => '/admin/registrations',
                'icon'  => 'fas fa-user-plus',
                'color' => 'info',
            ],
        ],
        [
            'type'  => 'App\\Notifications\\NuovaIscrizioneQuizNotification',
            'data'  => [
                'title' => 'Nuova iscrizione esame',
                'body'  => 'Luca Bianchi si è iscritto all\'esame "Quiz di esame #03 da 30 domande".',
                'url'   => '/admin/quizzes',
                'icon'  => 'fas fa-file-signature',
                'color' => 'warning',
            ],
        ],
        [
            'type'  => 'App\\Notifications\\QuizEsameCompletatoNotification',
            'data'  => [
                'title' => 'Esame completato',
                'body'  => 'Marco Rossi ha completato «Quiz di esame #01»: 27/30.',
                'url'   => '/admin/quizzes/confirmed-results',
                'icon'  => 'fas fa-flag-checkered',
                'color' => 'info',
            ],
        ],
        [
            'type'  => 'App\\Notifications\\QuizConfermatoNotification',
            'data'  => [
                'title' => 'Quiz confermato',
                'body'  => 'Il quiz "Simulazione esame B – sessione estiva" è stato confermato e pubblicato.',
                'url'   => '/admin/quizzes',
                'icon'  => 'fas fa-check-double',
                'color' => 'success',
            ],
        ],
    ];

    public function run(): void
    {
        $viewers = User::where('role', User::ROLE_VIEWER)->get();
        $admins  = User::whereIn('role', [User::ROLE_ADMIN, User::ROLE_EDITOR])->get();
        $now     = now();
        $total   = 0;

        // Notifiche per i viewer
        foreach ($viewers as $viewer) {
            $pool  = collect(self::VIEWER_NOTIFICATIONS)->shuffle();
            $count = fake()->numberBetween(2, min(4, $pool->count()));

            foreach ($pool->take($count) as $tpl) {
                $createdAt = $now->copy()->subDays(fake()->numberBetween(0, 14));
                $isRead    = fake()->boolean(50);

                DB::table('notifications')->insert([
                    'id'              => (string) Str::uuid(),
                    'type'            => $tpl['type'],
                    'notifiable_type' => User::class,
                    'notifiable_id'   => $viewer->id,
                    'data'            => json_encode($tpl['data']),
                    'read_at'         => $isRead ? $createdAt->copy()->addHours(fake()->numberBetween(1, 48)) : null,
                    'created_at'      => $createdAt,
                    'updated_at'      => $createdAt,
                ]);
                $total++;
            }
        }

        // Notifiche per admin/editor
        foreach ($admins as $admin) {
            $pool  = collect(self::ADMIN_NOTIFICATIONS)->shuffle();
            $count = fake()->numberBetween(3, $pool->count());

            foreach ($pool->take($count) as $tpl) {
                $createdAt = $now->copy()->subDays(fake()->numberBetween(0, 10));
                $isRead    = fake()->boolean(40);

                DB::table('notifications')->insert([
                    'id'              => (string) Str::uuid(),
                    'type'            => $tpl['type'],
                    'notifiable_type' => User::class,
                    'notifiable_id'   => $admin->id,
                    'data'            => json_encode($tpl['data']),
                    'read_at'         => $isRead ? $createdAt->copy()->addHours(fake()->numberBetween(1, 24)) : null,
                    'created_at'      => $createdAt,
                    'updated_at'      => $createdAt,
                ]);
                $total++;
            }
        }

        $this->command->info("CREATI {$total} NOTIFICHE DB ({$viewers->count()} viewer, {$admins->count()} admin/editor)");
    }
}
