# Notifiche — ScuolaGUIDA

Mappa di tutte le notifiche, canali e punti di dispatch.

## Notifiche viewer

| Classe | Canali | Dispatch in | Evento |
|---|---|---|---|
| `RegistrazioneApprovataNotification` | mail + database + webpush | `UserRegistrationService::approve()` | Anagrafica approvata |
| `RegistrazioneRifiutataNotification` | mail + database | `UserRegistrationService::reject()` | Anagrafica rifiutata |
| `IscrizioneQuizApprovataNotification` | mail + database | `QuizEnrollmentService::approve()` | Iscrizione quiz approvata |
| `IscrizioneQuizRifiutataNotification` | mail + database | `QuizEnrollmentService::reject()`, `enrollments:close-expired` | Iscrizione quiz rifiutata |
| `IscrizioneQuizRiapertaNotification` | mail + database | `QuizEnrollmentService::reopen()` | Iscrizione riaperta da admin |
| `QuizConfermatoNotification` | mail + database | `QuizService::confirm()` | Nuovo quiz confermato disponibile |
| `RuoloAggiornatoNotification` | mail + database | `UserService::update()` | Ruolo utente modificato |
| `BadgeEarned` | database + webpush | `BadgeService::awardIfEligible()` | Badge guadagnato |
| `SpacedRepetitionReminderNotification` | webpush | `push:send-review-reminders` | Promemoria ripasso SM-2 |

## Notifiche admin

| Classe | Canali | Dispatch in | Evento |
|---|---|---|---|
| `NuovaRichiestaAnagraficaNotification` | mail + database | `UserRegistrationService::submit()` | Nuova richiesta anagrafica viewer |
| `AnagraficaModificataNotification` | mail + database | `UserRegistrationService::submit()` | Viewer già approvato reinvia anagrafica |
| `NuovaIscrizioneQuizNotification` | mail + database | `QuizEnrollmentService::request()` | Viewer richiede iscrizione quiz |
| `QuizEsameCompletatoNotification` | mail + database | `QuizEnrollmentService::markCompleted()` | Viewer completa un quiz ufficiale |
| `BackupFailed` | mail + database | `SendBackupFailedNotification` listener | `BackupHasFailed` event (spatie) |

## Notifiche instructor

| Classe | Canali | Dispatch in | Evento |
|---|---|---|---|
| `InstructorStudentOutcome` | mail + database + webpush | `QuizAttemptService::record()` | Studente completa un quiz |

## Regole di dispatch

- Le notifiche applicative passano preferibilmente per `NotificationService::send()` o `sendToAdmins()`
- Eccezioni attuali: `BadgeService::awardIfEligible()` e `SendSpacedRepetitionReminders` usano `$user->notify(...)` direttamente
- Le classi notification queued impostano `->onQueue('emails')` nel costruttore
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
