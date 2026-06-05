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
            ? __('notifications.sr_push_body_one')
            : __('notifications.sr_push_body_many', ['count' => $this->dueCount]);

        return (new WebPushMessage())
            ->title(__('notifications.sr_push_title'))
            ->body($body)
            ->icon('/icons/icon-192.png')
            ->action(__('notifications.sr_push_action'), route('viewer.smart-review.index'));
    }
}
