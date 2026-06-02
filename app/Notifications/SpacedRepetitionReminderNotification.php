<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use NotificationChannels\WebPush\WebPushChannel;
use NotificationChannels\WebPush\WebPushMessage;

class SpacedRepetitionReminderNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public int $dueCount)
    {
        $this->onQueue('emails');
    }

    public function via(object $notifiable): array
    {
        return [WebPushChannel::class];
    }

    public function toWebPush(object $notifiable, object $notification): WebPushMessage
    {
        $body = $this->dueCount === 1
            ? 'Hai 1 domanda in scadenza oggi — dedicale 2 minuti!'
            : "Hai {$this->dueCount} domande in scadenza oggi — dedicaci qualche minuto!";

        return (new WebPushMessage())
            ->title('Ripasso intelligente')
            ->body($body)
            ->icon('/icons/icon-192.png')
            ->action('Inizia il ripasso', route('viewer.smart-review.index'));
    }
}
