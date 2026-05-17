<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification as NotificationFacade;
use Throwable;

/**
 * Le email del flusso iscrizioni sono fire-and-forget:
 * passano sempre dalla coda 'emails' (Notification ShouldQueue)
 * e qualsiasi errore di dispatch viene loggato senza propagare,
 * così il workflow utente non si blocca mai.
 */
class NotificationService
{
    /**
     * Invia la notifica a un singolo utente (o a una collection).
     */
    public function send(mixed $notifiables, Notification $notification): void
    {
        try {
            NotificationFacade::send($notifiables, $notification);
        } catch (Throwable $e) {
            Log::warning('Notification dispatch failed', [
                'notification' => $notification::class,
                'error'        => $e->getMessage(),
            ]);
        }
    }

    /**
     * Invia la notifica a tutti gli admin (utenti con ruolo admin).
     */
    public function sendToAdmins(Notification $notification): void
    {
        $admins = User::where('role', User::ROLE_ADMIN)->get();

        if ($admins->isEmpty()) {
            return;
        }

        $this->send($admins, $notification);
    }
}
