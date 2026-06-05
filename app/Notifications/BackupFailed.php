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
            ->subject('[' . config('app.name') . '] ' . __('notifications.backup_failed_subject'))
            ->greeting(__('notifications.backup_failed_mail_title'))
            ->line(__('notifications.backup_failed_mail_body'))
            ->line('Error: ' . $this->sanitizedMessage())
            ->action(__('notifications.backup_failed_mail_cta'), route('admin.health.index'));
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'title' => __('notifications.backup_failed_subject'),
            'body'  => __('notifications.backup_failed_mail_body') . ' ' . $this->sanitizedMessage(),
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
