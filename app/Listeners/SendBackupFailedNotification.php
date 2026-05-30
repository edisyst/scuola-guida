<?php

namespace App\Listeners;

use App\Notifications\BackupFailed;
use App\Services\NotificationService;
use Spatie\Backup\Events\BackupHasFailed;

class SendBackupFailedNotification
{
    public function __construct(private NotificationService $notifications) {}

    public function handle(BackupHasFailed $event): void
    {
        $message = $event->exception->getMessage();

        $this->notifications->sendToAdmins(new BackupFailed($message));
    }
}
