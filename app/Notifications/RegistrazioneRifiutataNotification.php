<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class RegistrazioneRifiutataNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public ?string $motivazione = null)
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
            ->subject(__('notifications.reg_rejected_subject'))
            ->markdown('emails.registrazione-rifiutata', [
                'user'        => $notifiable,
                'motivazione' => $this->motivazione,
                'appUrl'      => config('app.url'),
            ]);
    }

    public function toDatabase(object $notifiable): array
    {
        $body = __('notifications.reg_rejected_db_body');

        if ($this->motivazione) {
            $body .= ' Motivo: ' . \Illuminate\Support\Str::limit($this->motivazione, 60);
        }

        return [
            'title' => __('notifications.reg_rejected_db_title'),
            'body'  => $body,
            'url'   => route('profile.edit'),
            'icon'  => 'fas fa-times-circle',
            'color' => 'danger',
        ];
    }
}
