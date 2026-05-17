<?php

namespace App\Services;

use App\Models\User;
use App\Notifications\AnagraficaModificataNotification;
use App\Notifications\NuovaRichiestaAnagraficaNotification;
use App\Notifications\RegistrazioneApprovataNotification;
use App\Notifications\RegistrazioneRifiutataNotification;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class UserRegistrationService
{
    private const DOCUMENT_DISK = 'public';
    private const DOCUMENT_DIR  = 'registrations';

    public function __construct(private NotificationService $notifications) {}

    /**
     * Il viewer invia (o reinvia) i suoi dati anagrafici per essere
     * abilitato agli esami ufficiali. Se aveva già stato approvato,
     * un reinvio lo riporta in 'pending' e disabilita l'iscrizione
     * a nuovi esami fino alla riapprovazione.
     */
    public function submit(User $user, array $data, ?UploadedFile $document = null): User
    {
        if (!$user->isViewer()) {
            throw new RuntimeException('Solo gli utenti viewer possono inviare la richiesta di iscrizione.');
        }

        // Cattura lo stato precedente PRIMA del fill: se l'utente era già approvato,
        // il re-invio toglie l'abilitazione agli esami e richiede una nuova revisione.
        $wasApproved = $user->isRegistrationApproved();

        if ($document) {
            $this->deleteDocument($user);
            $data['id_document_path'] = $document->store(self::DOCUMENT_DIR, self::DOCUMENT_DISK);
        }

        $data['registration_status']           = User::REG_PENDING;
        $data['registration_submitted_at']     = now();
        $data['registration_reviewed_at']      = null;
        $data['registration_reviewed_by']      = null;
        $data['registration_rejection_reason'] = null;

        $user->fill($data);
        $user->save();

        $user->refresh();

        $notification = $wasApproved
            ? new AnagraficaModificataNotification($user)
            : new NuovaRichiestaAnagraficaNotification($user);

        $this->notifications->sendToAdmins($notification);

        return $user;
    }

    public function approve(User $user, User $admin): User
    {
        if (!$user->isRegistrationPending()) {
            throw new RuntimeException('Solo le richieste in attesa possono essere approvate.');
        }

        $user->update([
            'registration_status'           => User::REG_APPROVED,
            'registration_reviewed_at'      => now(),
            'registration_reviewed_by'      => $admin->id,
            'registration_rejection_reason' => null,
        ]);

        $user->refresh();

        $this->notifications->send($user, new RegistrazioneApprovataNotification());

        return $user;
    }

    public function reject(User $user, User $admin, ?string $reason = null): User
    {
        if (!$user->isRegistrationPending()) {
            throw new RuntimeException('Solo le richieste in attesa possono essere rifiutate.');
        }

        $user->update([
            'registration_status'           => User::REG_REJECTED,
            'registration_reviewed_at'      => now(),
            'registration_reviewed_by'      => $admin->id,
            'registration_rejection_reason' => $reason,
        ]);

        $user->refresh();

        $this->notifications->send($user, new RegistrazioneRifiutataNotification($reason));

        return $user;
    }

    public function documentUrl(User $user): ?string
    {
        if (!$user->id_document_path) {
            return null;
        }

        return Storage::disk(self::DOCUMENT_DISK)->url($user->id_document_path);
    }

    private function deleteDocument(User $user): void
    {
        if ($user->id_document_path && Storage::disk(self::DOCUMENT_DISK)->exists($user->id_document_path)) {
            Storage::disk(self::DOCUMENT_DISK)->delete($user->id_document_path);
        }
    }
}
