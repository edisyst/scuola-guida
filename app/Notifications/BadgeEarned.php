<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use NotificationChannels\WebPush\WebPushChannel;
use NotificationChannels\WebPush\WebPushMessage;

class BadgeEarned extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public string $badgeCode,
        public array $metadata = [],
    ) {
        $this->onQueue('emails');
    }

    public function via(object $notifiable): array
    {
        return ['database', WebPushChannel::class];
    }

    public function toDatabase(object $notifiable): array
    {
        $badge = config('badges.' . $this->badgeCode, []);

        return [
            'title' => 'Hai guadagnato un badge: ' . ($badge['name'] ?? $this->badgeCode),
            'body'  => $badge['description'] ?? '',
            'url'   => route('viewer.profile.badges'),
            'icon'  => $badge['icon'] ?? 'fas fa-award',
            'color' => $badge['color'] ?? 'success',
        ];
    }

    public function toWebPush(object $notifiable, object $notification): WebPushMessage
    {
        $badge = config('badges.' . $this->badgeCode, []);

        return (new WebPushMessage())
            ->title('Nuovo badge: ' . ($badge['name'] ?? $this->badgeCode))
            ->body($badge['description'] ?? 'Hai guadagnato un nuovo badge!')
            ->icon('/icons/icon-192.png')
            ->action('Vedi badge', route('viewer.profile.badges'));
    }
}
