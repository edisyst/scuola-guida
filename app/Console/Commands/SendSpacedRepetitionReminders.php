<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Notifications\SpacedRepetitionReminderNotification;
use App\Services\SpacedRepetitionService;
use Illuminate\Console\Command;

class SendSpacedRepetitionReminders extends Command
{
    protected $signature = 'push:send-review-reminders';
    protected $description = 'Invia notifiche push ai viewer con domande SM-2 in scadenza oggi';

    public function handle(SpacedRepetitionService $spacedRepetition): int
    {
        $sent = 0;

        User::where('role', User::ROLE_VIEWER)
            ->whereHas('pushSubscriptions')
            ->lazy()
            ->each(function (User $user) use ($spacedRepetition, &$sent) {
                $dueCount = $spacedRepetition->getUpcomingCount($user)['due_today'];

                if ($dueCount > 0) {
                    $user->notify(new SpacedRepetitionReminderNotification($dueCount));
                    $sent++;
                }
            });

        $this->info("Promemoria inviati: {$sent}");

        return self::SUCCESS;
    }
}
