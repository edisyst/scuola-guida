<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class RuoloAggiornatoNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public string $oldRole, public string $newRole)
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
            ->subject(__('notifications.role_updated_subject'))
            ->markdown('emails.ruolo-aggiornato', [
                'user'      => $notifiable,
                'oldLabel'  => $this->roleLabel($this->oldRole),
                'newLabel'  => $this->roleLabel($this->newRole),
                'appUrl'    => config('app.url'),
            ]);
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'title' => __('notifications.role_updated_db_title'),
            'body'  => 'Il tuo ruolo è stato cambiato da "'
                . $this->roleLabel($this->oldRole)
                . '" a "'
                . $this->roleLabel($this->newRole) . '".',
            'url'   => route('dashboard'),
            'icon'  => 'fas fa-user-tag',
            'color' => 'info',
        ];
    }

    private function roleLabel(string $role): string
    {
        return User::ROLES[$role] ?? $role;
    }
}
