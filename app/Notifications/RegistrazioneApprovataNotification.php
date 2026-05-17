<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class RegistrazioneApprovataNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct()
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
        $appUrl = config('app.url');

        return (new MailMessage())
            ->subject('Iscrizione anagrafica approvata')
            ->markdown('emails.registrazione-approvata', [
                'user'   => $notifiable,
                'appUrl' => $appUrl,
            ]);
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'title' => 'Iscrizione approvata',
            'body'  => 'La tua iscrizione anagrafica è stata approvata: ora puoi iscriverti agli esami ufficiali.',
            'url'   => route('dashboard'),
            'icon'  => 'fas fa-check-circle',
            'color' => 'success',
        ];
    }
}
