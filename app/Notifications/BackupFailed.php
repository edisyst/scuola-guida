<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BackupFailed extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly string $errorMessage)
    {
        $this->onQueue('emails');
    }

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->error()
            ->subject('[' . config('app.name') . '] Backup fallito')
            ->greeting('Attenzione!')
            ->line('Il backup automatico dell\'applicazione non è riuscito.')
            ->line('Errore: ' . $this->sanitizedMessage())
            ->line('Verificare i log di sistema per maggiori dettagli.')
            ->action('Vai alla Health Dashboard', route('admin.health.index'));
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'title' => 'Backup fallito',
            'body'  => 'Errore durante il backup automatico: ' . $this->sanitizedMessage(),
            'url'   => route('admin.health.index'),
            'icon'  => 'fas fa-exclamation-triangle',
            'color' => 'danger',
        ];
    }

    private function sanitizedMessage(): string
    {
        // Tronca e rimuove path assoluti per non esporre la struttura filesystem
        $message = preg_replace('/[A-Za-z]:\\\\[^\s]+|\/[^\s]+/', '[path]', $this->errorMessage);

        return mb_substr($message ?? $this->errorMessage, 0, 200);
    }
}
