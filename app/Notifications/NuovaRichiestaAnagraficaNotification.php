<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NuovaRichiestaAnagraficaNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public User $viewer)
    {
        $this->onQueue('emails');
    }

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        /** @var User $notifiable */
        return (new MailMessage())
            ->subject('Nuova richiesta di iscrizione anagrafica')
            ->markdown('emails.nuova-richiesta-anagrafica', [
                'admin'  => $notifiable,
                'viewer' => $this->viewer,
                'appUrl' => config('app.url'),
            ]);
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'title' => 'Nuova richiesta anagrafica',
            'body'  => $this->viewer->fullAnagraphicName() . ' ha inviato i dati anagrafici per l\'approvazione.',
            'url'   => route('admin.registrations.show', $this->viewer),
            'icon'  => 'fas fa-id-card',
            'color' => 'warning',
        ];
    }
}
