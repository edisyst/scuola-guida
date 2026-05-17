<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AnagraficaModificataNotification extends Notification implements ShouldQueue
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
            ->subject('Anagrafica modificata — richiesta nuova revisione')
            ->markdown('emails.anagrafica-modificata', [
                'admin'  => $notifiable,
                'viewer' => $this->viewer,
                'appUrl' => config('app.url'),
            ]);
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'title' => 'Anagrafica modificata',
            'body'  => $this->viewer->fullAnagraphicName() . ' ha modificato i dati anagrafici dopo l\'approvazione: nuova revisione richiesta.',
            'url'   => route('admin.registrations.show', $this->viewer),
            'icon'  => 'fas fa-exclamation-triangle',
            'color' => 'warning',
        ];
    }
}
