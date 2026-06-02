<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use NotificationChannels\WebPush\WebPushChannel;
use NotificationChannels\WebPush\WebPushMessage;

class RegistrazioneApprovataNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct()
    {
        $this->onQueue('emails');
    }

    public function via(object $notifiable): array
    {
        return ['mail', 'database', WebPushChannel::class];
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

    public function toWebPush(object $notifiable, object $notification): WebPushMessage
    {
        return (new WebPushMessage())
            ->title('Iscrizione approvata')
            ->body('La tua iscrizione è stata approvata: ora puoi iscriverti agli esami ufficiali.')
            ->icon('/icons/icon-192.png')
            ->action('Apri dashboard', route('dashboard'));
    }
}
