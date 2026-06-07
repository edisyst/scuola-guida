# Notifiche — ScuolaGUIDA

Mappa di tutte le notifiche, canali e punti di dispatch.

## Notifiche viewer

| Classe | Canali | Dispatch in | Evento |
|---|---|---|---|
| `RegistrazioneApprovataNotification` | mail + database + webpush | `Admin\RegistrationController::approve()` | Anagrafica approvata |
| `RegistrazioneRifiutataNotification` | mail + database | `Admin\RegistrationController::reject()` | Anagrafica rifiutata |
| `IscrizioneQuizApprovataNotification` | mail + database | `QuizEnrollmentController::approve()` | Iscrizione quiz approvata |
| `IscrizioneQuizRifiutataNotification` | mail + database | `QuizEnrollmentController::reject()`, `enrollments:close-expired` | Iscrizione quiz rifiutata |
| `RuoloAggiornatoNotification` | mail + database | `Admin\RolePermissionController::update()` | Ruolo utente modificato |
| `RegistrazioneAnagraficaModificataNotification` | mail + database | `Admin\RegistrationController::update()` | Dati anagrafica modificati da admin |
| `BadgeEarned` | database + webpush | `BadgeService::awardIfEligible()` | Badge guadagnato |
| `SpacedRepetitionReminderNotification` | webpush | `push:send-review-reminders` | Promemoria ripasso SM-2 |

## Notifiche admin

| Classe | Canali | Dispatch in | Evento |
|---|---|---|---|
| `BackupFailed` | mail + database | `SendBackupFailedNotification` listener | `BackupHasFailed` event (spatie) |
| `NewReport` | mail + database | `ReportController::show()` | Report generato (notify admin+editor) |

## Notifiche instructor

| Classe | Canali | Dispatch in | Evento |
|---|---|---|---|
| `InstructorStudentOutcome` | mail + database + webpush | `QuizAttemptService::record()` | Studente completa un quiz |

## Regole di dispatch

- **Tutte** le notifiche passano per `NotificationService::sendToUser()` o `sendToAdmins()`
- **Sempre** con `->onQueue('emails')` (fire-and-forget)
- **Non** chiamare `Notification::send()` direttamente nei controller
- Le notifiche non bloccano mai il workflow dell'utente

## Web Push (Feature 6.7)

- Canale: `laravel-notification-channels/webpush`
- Chiavi VAPID in `.env`: `VAPID_PUBLIC_KEY`, `VAPID_PRIVATE_KEY`, `VAPID_SUBJECT`
- Viewer si iscrive/disiscrive dal profilo (blocco Alpine in `/profile`)
- Meta tag `<meta name="vapid-public-key">` nel layout `layouts.admin`
- Handler `push` e `notificationclick` in `public/sw.js`

## i18n nelle notifiche

- `User` implementa `HasLocalePreference` → le notifiche in coda vengono renderizzate nel locale dell'utente automaticamente
- Le chiavi di traduzione sono in `lang/{locale}/notifications.php`
