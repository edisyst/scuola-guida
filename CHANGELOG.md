# Changelog

Tutte le modifiche significative a questo progetto sono documentate in questo file.
Formato seguente [Keep a Changelog](https://keepachangelog.com/it/1.0.0/).

---

## [Unreleased] — Feature 4.2: GDPR anonimizzazione utenti

### Changed

- **`gdpr:list`** — aggiunta opzione `--anonymized`: filtra e mostra solo i viewer il cui indirizzo email termina con `@eliminato.invalid` (cioè già anonimizzati). Senza opzione il comportamento è invariato (tutti i viewer). Messaggio empty-state contestualizzato in base al flag.
- **Test** (`tests/Feature/GdprTest.php`) — aggiunti 3 nuovi test: filtro `--anonymized` mostra solo gli utenti anonimizzati, utenti attivi esclusi dal filtro, empty-state corretto quando nessun viewer è stato anonimizzato.

### Files

```
app/Console/Commands/GdprList.php   # +--anonymized option + empty-state contestuale
tests/Feature/GdprTest.php          # 3 nuovi test per --anonymized
README.md                           # gdpr:list --anonymized documentato nella sezione GDPR
```

---

## [Unreleased] — Feature 4.1: evoluzione struttura answers su QuizAttempt

Migrazione non-distruttiva del campo `answers` su `QuizAttempt` da formato flat
`{"12": 1}` a formato esteso `{"12": {"correct": 1, "answered_at": ..., "time_spent_seconds": ..., "position": ...}}`.
Tutti i punti di lettura passano ora per i metodi accessori del model.

### Added

- `QuizAttempt::getAnsweredAt(int|string $questionId): ?Carbon` — restituisce il
  timestamp Carbon della risposta, o `null` per formato flat o campo assente.
- `QuizAttempt::getTimeSpent(int|string $questionId): ?int` — secondi impiegati
  sulla domanda, o `null` per formato flat o campo assente.
- `QuizAttempt::getAnswerPosition(int|string $questionId): ?int` — posizione
  progressiva nella sessione, o `null` per formato flat.
- Feature test `tests/Feature/QuizTest.php`: test per i tre nuovi accessori;
  test idempotenza `up()` su dataset misto (flat + esteso); test `down()` con
  rollback a flat e skip dei record già flat.

### Changed

- `QuizAttemptService::getAttemptDetail()` — sostituiti gli accessi diretti a
  `$rawEntry['position']` e `$rawEntry['time_spent_seconds']` con
  `$attempt->getAnswerPosition()` e `$attempt->getTimeSpent()`.
- `SimulatorService::getResultDetail()` — stessa sostituzione; l'iterazione passa
  ora per `collect($answeredQids)` anziché per `collect($answersData)`, con accesso
  ai valori tramite `$attempt->getAnswerResult()`, `->getAnswerPosition()`,
  `->getTimeSpent()`.

### Files

```
app/Models/QuizAttempt.php                                      # tre nuovi accessor
app/Services/QuizAttemptService.php                             # getAttemptDetail usa accessor
app/Services/SimulatorService.php                               # getResultDetail usa accessor
tests/Feature/QuizTest.php                                      # accessor + migration tests
```

---

## [Unreleased] — Feature 3.6: calendario sessioni d'esame

Pagina `/calendar` lato viewer con lista cronologica dei quiz confermati divisa
in tre sezioni, widget "Prossima sessione" con countdown Alpine.js nella dashboard
viewer, voce "Calendario sessioni" già presente nella sidebar.

### Added

- `CalendarController::index()` — tre query distinte con eager loading della
  relazione `enrollments` filtrata per `user_id` dell'utente autenticato.
  Sezioni: `upcoming` (orderBy `enrollments_open_at` asc), `open` (orderBy
  `enrollments_close_at` asc), `closed` (orderBy `enrollments_close_at` desc,
  limit 10). Variabile `$canEnroll` coerente con il catalogo quiz.

- `resources/views/calendar/index.blade.php` — tre sezioni (Prossime sessioni,
  Iscrizioni aperte, Sessioni chiuse). Le sezioni 1 e 2 appaiono solo se non
  vuote; la sezione 3 mostra sempre l'empty state con icona `fa-3x text-muted`.
  Script Alpine.js `countdown(targetTimestamp)` via `@push('js')`.

- `resources/views/calendar/_quiz-row.blade.php` — partial condiviso tra le
  tre sezioni. Usa `$quiz->enrollments->first()` dall'eager loading. Badge
  stato iscrizione personale: per le sessioni chiuse mostra l'esito specifico
  (In attesa / Approvata / Rifiutata / Completata); per upcoming e open mostra
  "Già iscritto". Countdown Alpine.js per i quiz upcoming. Pulsante "Richiedi
  iscrizione" riusa esattamente lo stesso pattern del catalogo quiz confermati.

- Widget "Prossima sessione" nella dashboard viewer (`stats/dashboard.blade.php`)
  aggiornato con countdown Alpine.js per le sessioni upcoming; colore
  `bg-gradient-warning` per upcoming, `bg-gradient-success` per open.
  Funzione `countdown()` aggiunta a `@section('js')`.

### Files

```
app/Http/Controllers/CalendarController.php        # eager loading + fix ordering $open
resources/views/calendar/index.blade.php           # tre sezioni, empty state, countdown script
resources/views/calendar/_quiz-row.blade.php       # enrollment object, badge stato sezione chiusa
resources/views/stats/dashboard.blade.php          # widget nextSession con countdown Alpine.js
```

---

## [Unreleased] — Feature 3.5: schedulazione apertura/chiusura iscrizioni

Aggiunta finestra di schedulazione (`enrollments_open_at` / `enrollments_close_at`) sui quiz confermati
per controllare quando i viewer possono richiedere l'iscrizione.

### Model Quiz

- `getEnrollmentStatusAttribute()` aggiornato: restituisce `not_scheduled` (invariato rispetto al comportamento precedente) quando entrambi i campi sono `null`. Stati: `not_scheduled` / `open` / `upcoming` / `closed`.

### StoreQuizRequest

Aggiunte regole per i due nuovi campi (coerenti con `UpdateQuizScheduleRequest`):
- `enrollments_open_at`: `nullable|date`
- `enrollments_close_at`: `nullable|date|after:enrollments_open_at`

### Form creazione quiz (`partials/form.blade.php`)

Due campi `datetime-local` aggiunti alla fine del form (visibili solo agli admin):
- *Apertura iscrizioni* (`enrollments_open_at`)
- *Chiusura iscrizioni* (`enrollments_close_at`)

Valori pre-popolati in edit con `optional(...)->format('Y-m-d\TH:i')`.

### Comando `enrollments:close-expired`

Refactoring del comando `CloseExpiredEnrollments`:
- Usa `QuizEnrollmentService::reject()` per ogni iscrizione invece del bulk `UPDATE` diretto, in modo da non duplicare la logica di rifiuto e inviare le notifiche agli utenti.
- `->lazy()` su entrambe le query (quiz e enrollment) per iterare senza caricare tutto in memoria.
- Trova il primo utente admin come reviewer di sistema; se assente logga errore e restituisce `FAILURE`.
- Idempotente: seleziona solo iscrizioni `pending` su quiz con `enrollments_close_at <= now()`.

### Files

```
app/Http/Requests/StoreQuizRequest.php                    # +enrollments_open_at, +enrollments_close_at
app/Models/Quiz.php                                       # getEnrollmentStatusAttribute: +not_scheduled
app/Console/Commands/CloseExpiredEnrollments.php          # refactoring: service + lazy()
resources/views/admin/quizzes/partials/form.blade.php     # +2 campi datetime-local (admin only)
resources/views/calendar/_quiz-row.blade.php              # +not_scheduled trattato come 'open'
resources/views/stats/dashboard.blade.php                 # widget nextSession: gestione not_scheduled
tests/Feature/CalendarTest.php                            # test accessor aggiornato a 'not_scheduled'
tests/Feature/AdminOperativityTest.php                    # +admin() prima del comando nel test
```

---

## [Unreleased] — Feature 3.4: pannello riepilogo quiz confermato

Verifica e consolidamento del pannello `/admin/quizzes/{quiz}/summary`. La feature era già implementata nelle versioni precedenti; questa entry documenta formalmente l'architettura e registra le correzioni estetiche alle KPI box per allinearle allo spec ufficiale.

### QuizSummaryService (`app/Services/QuizSummaryService.php`)

`getSummary(Quiz $quiz): array` restituisce:

- **`kpi`** — `total` (iscrizioni `approved` + `completed`), `completed` (iscrizioni con tentativo), `pending` (approvati senza tentativo), `average_score` (media % sui completati, `null` se nessuno ha completato), `pass_rate` (% promossi, `null` se nessuno ha completato).
- **`enrollments`** — `Collection<QuizEnrollment>` con eager loading `user` + `quizAttempt`, ordinata per cognome ASC, senza query N+1.

`isPassed(QuizAttempt $attempt, Quiz $quiz): bool` — "Promosso" se `total_questions - score <= quiz.max_errors`. Logica condivisa con `QuizResultsExport`, nessuna duplicazione.

### Controller e route

`QuizController::summary(Quiz $quiz, QuizSummaryService $summaries)`:
- Autorizzazione: `abort_unless(auth()->user()->isAdmin(), 403)`.
- Stato quiz: `abort_unless($quiz->isConfirmed(), 404)`.
- Tutta la logica di aggregazione delegata a `QuizSummaryService`.

Route `GET /admin/quizzes/{quiz}/summary` → `admin.quizzes.summary`, gruppo `middleware('role:admin')`.
Pulsante "Riepilogo" (`fas fa-chart-bar`) nella lista quiz admin (`admin.quizzes.index`), visibile solo per quiz in stato `confirmed`.

### View (`resources/views/admin/quizzes/summary.blade.php`)

Quattro `small-box` AdminLTE nella prima riga:

| Box | Colore | Icona |
|---|---|---|
| Totale iscritti | `bg-primary` (blu) | `fas fa-users` |
| Hanno completato | `bg-success` (verde) | `fas fa-check` |
| Non ancora svolto | `bg-warning` (giallo) | `fas fa-clock` |
| Punteggio medio | `bg-teal` (verde acqua) | `fas fa-chart-bar` |

Punteggio medio: 1 decimale + `%`, oppure `—` se nessun completato.

Tabella iscritti: Cognome · Nome · Email · Stato iscrizione (badge) · Punteggio · Percentuale · Esito · Data tentativo. Righe `table-success` (Promosso) / `table-danger` (Rimandato) / `table-warning` (Non svolto). Pulsante "Esporta Excel" per gli utenti con `canEditQuiz()`.

### Correzioni estetiche (2026-05-21)

Allineamento delle KPI box allo spec di Feature 3.4:

| Box | Prima | Dopo |
|---|---|---|
| Totale iscritti | `bg-info` | `bg-primary` |
| Hanno completato | `fa-check-circle` | `fa-check` |
| Non ancora svolto | `fa-hourglass-half` | `fa-clock` |
| Punteggio medio | `bg-primary` + `fa-star` | `bg-teal` + `fa-chart-bar` |

### Files

```
app/Services/QuizSummaryService.php                # getSummary() + isPassed()
app/Http/Controllers/QuizController.php            # summary() + exportResults() con QuizSummaryService
app/Exports/QuizResultsExport.php                  # isPassed() delegato a QuizSummaryService (DRY)
resources/views/admin/quizzes/summary.blade.php    # correzioni estetiche KPI box
routes/web.php                                     # GET /admin/quizzes/{quiz}/summary
```

---

## [Unreleased] — Feature 3.3: export Excel risultati quiz confermati

Aggiunto pulsante "Esporta Excel" nella pagina di riepilogo di un quiz confermato. Il download è sincrono e produce un file `.xlsx` con una riga per ogni iscritto approvato/completato al quiz.

### Export (`app/Exports/QuizResultsExport.php`)

Classe `QuizResultsExport` che implementa `FromQuery`, `ShouldAutoSize`, `WithHeadings`, `WithMapping`, `WithStyles`.

- **FromQuery**: query su `QuizEnrollment` filtrando gli stati `approved` e `completed`, con eager loading `['user', 'quizAttempt']`, join su `users` per ordinamento per cognome e nome.
- **WithHeadings**: otto colonne in italiano — Cognome, Nome, Email, Data tentativo, Punteggio, Percentuale, Esito, Durata (minuti).
- **WithMapping**: per ogni enrollment calcola punteggio intero, percentuale con 1 decimale e simbolo `%`, esito (`Promosso` / `Rimandato` / `Non svolto`) riutilizzando la stessa logica `max_errors` già presente nel progetto (`errors = total_questions - score; passed = errors <= quiz.max_errors`), durata in minuti interi arrotondati dal campo `duration` in secondi.
- **WithStyles**: intestazione in grassetto + sfondo grigio chiaro (`#D3D3D3`).
- **ShouldAutoSize**: larghezza colonne adattata automaticamente al contenuto.

### Controller e route

`QuizController::exportResults(Quiz $quiz)` (già presente, aggiornato):
- Autorizzazione: `abort_unless(auth()->user()->canEditQuiz(), 403)` — accesso limitato agli utenti con permesso `edit_quiz`.
- Stato quiz: `abort_unless($quiz->isConfirmed(), 403)` — solo quiz confermati esportabili.
- Nome file: `risultati-{slug-titolo}-{YYYY-MM-DD}.xlsx`.

Route `GET /admin/quizzes/{quiz}/export-results` → `admin.quizzes.export-results` già presente nel gruppo middleware admin.

### View

Pulsante "Esporta Excel" (`btn-success`, icona `fas fa-file-excel`) nella sezione azioni della pagina `admin.quizzes.summary`, visibile solo agli utenti con `canEditQuiz()`.

---

## [Unreleased] — Feature 3.2: notifiche in-app con badge navbar

UI in-app per le notifiche database già emesse dalla Feature 3.1: campanella in topbar con badge contatore + dropdown delle ultime 10, pagina dedicata con elenco completo e azioni di pulizia. Chiusura del known issue sul `View::composer('*', ...)` per il contatore notifiche.

### Canale database (`app/Notifications/`)

Le sette Notification class previste dallo scope 3.2 (`RegistrazioneApprovataNotification`, `RegistrazioneRifiutataNotification`, `AnagraficaModificataNotification`, `NuovaRichiestaAnagraficaNotification`, `IscrizioneQuizApprovataNotification`, `IscrizioneQuizRifiutataNotification`, `IscrizioneQuizRiapertaNotification`, `NuovaIscrizioneQuizNotification`) — più le tre fuori scope ma allineate (`QuizConfermatoNotification`, `QuizEsameCompletatoNotification`, `RuoloAggiornatoNotification`) — dichiarano `['mail', 'database']` in `via()` e implementano `toDatabase()` con payload uniforme `{title, body, url, icon, color}`. Codifica visiva coerente con l'evento: `fas fa-check-circle` + `success` per approvazioni, `fas fa-times-circle` + `danger` per rifiuti, `fas fa-id-card` / `fas fa-user-clock` + `warning` per richieste agli admin, `fas fa-redo` / `fas fa-clipboard-check` / `fas fa-user-tag` + `info` per eventi neutri. Body troncato a ~40–60 caratteri sul titolo del quiz / motivazione per restare leggibile nel dropdown.

Tabella `notifications` (UUID PK, `notifiable` morph, `data` JSON, `read_at` nullable) creata via `php artisan notifications:table` nella migration `2026_05_17_161328_create_notifications_table.php`.

### Componente Livewire `NotificationBell` (`app/Http/Livewire/NotificationBell.php`)

Esposto in topbar AdminLTE tramite `<livewire:notification-bell />` nella section `content_top_nav_right` del layout `resources/views/layouts/admin.blade.php`. Render condizionato da `@auth`.

- Property pubblica `int $unreadCount` aggiornata da `loadNotifications()`. La collection delle ultime 10 notifiche è ricalcolata a ogni `render()` (no property serializzata pesante).
- `mount()` invoca `loadNotifications()` per evitare il flash a zero del badge al primo paint.
- `markAsRead(string $notificationId)` recupera la singola notifica via `$user->notifications()->whereKey($id)->first()` (zero possibilità di cross-user via id guess), la segna come letta, ricarica il contatore e — se il payload contiene `url` — restituisce un `$this->redirect($url, navigate: false)` per portare l'utente alla pagina collegata.
- `markAllAsRead()` invoca `$user->unreadNotifications->markAsRead()` e ricalcola il contatore.
- Polling: `wire:poll.30s="loadNotifications"` applicato al solo `<li>` del dropdown (non all'intera pagina) per aggiornare badge e dropdown senza riprocessare l'intero layout.

### View Livewire (`resources/views/livewire/notification-bell.blade.php`)

Struttura HTML conforme al pattern AdminLTE 3 (`nav-item dropdown` + `nav-link` + `dropdown-menu-lg dropdown-menu-right`). Badge `badge-warning navbar-badge` visibile solo se `$unreadCount > 0` con cap a `99+`. Header dropdown con conteggio non-lette (singolare/plurale) o "Nessuna notifica non letta". Pulsante "Segna tutte come lette" visibile solo se ci sono non-lette, con `wire:loading` mirato e spinner. Riga notifica con icona (`{{ $data['icon'] }}` colorata via `text-{{ $data['color'] }}`), titolo, body troncato CSS (`text-truncate`), tempo relativo (`diffForHumans()`); non-lette evidenziate con `font-weight-bold`. Footer "Tutte le notifiche" → `route('notifications.index')`.

### Pagina lista (`/notifications` — viewer/editor/admin)

`App\Http\Controllers\NotificationController` con tre action:

- **`index(Request)`** — `markAsRead()` su tutte le non-lette dell'utente all'ingresso pagina, poi `notifications()->paginate(20)` ordinato `created_at desc`. Restituisce `notifications.index`.
- **`destroy(Request, string $id)`** — recupera il record via `DatabaseNotification::findOrFail($id)`, verifica `notifiable_type` + `notifiable_id` matching l'utente autenticato (`abort 403` altrimenti), `delete()`. Flash `success`.
- **`destroyAll(Request)`** — `$user->notifications()->delete()` di massa per l'utente. Flash `success`.

Route nel gruppo `auth` (`routes/web.php`): `GET /notifications` → `index` (`notifications.index`), `DELETE /notifications` → `destroyAll` (`notifications.destroyAll`), `DELETE /notifications/{id}` → `destroy` (`notifications.destroy`). Niente policy ad-hoc: il filtro per `auth()->id()` nel controller è autorevole; il route param `{id}` è una UUID e il controllo di ownership previene l'IDOR.

View `resources/views/notifications/index.blade.php` in stile `sg-wrapper`: header con titolo + pulsante "Elimina tutte" (form DELETE con `onsubmit="return confirm()"`), card con tabella `sg-table` (icona colorata, titolo cliccabile sul link contestuale, body, data `d/m/Y H:i`, pulsante elimina per riga con confirm), paginazione standard. Empty state con messaggio dedicato. Le righe non-lette sono `font-weight-bold` ma la pagina chiama già `markAsRead()` all'ingresso: in pratica il bold si vede solo se l'utente arriva sulla pagina senza JS o se nuove notifiche si aggiungono dopo il render (caso polling).

### Sidebar (`config/adminlte.php`)

Voce "Notifiche" già nella sezione *AREA PERSONALE* (visibile a tutti gli autenticati, niente `can`: le notifiche sono personali). Rimosso `label_color => 'warning'` dato che il badge sidebar non viene più popolato: il contatore è ora esposto dalla campanella in topbar.

### Chiusura known issue: `View::composer('*', ...)`

Il known issue documentato nella sezione *Refactor cumulativo* riguardava `View::composer('*', ...)` in `AppServiceProvider` che girava su ogni view (anche nested). La parte relativa al contatore notifiche è stata rimossa:

- Eliminato il blocco `$unreadNotifications = auth()->check() ? auth()->user()->unreadNotifications()->where('created_at', '>=', $since)->count() : 0;` (query non-cacheabile per-utente, una per ogni view renderizzata).
- Eliminato il `case 'notifications'` nello switch del menu (il badge sidebar non viene più popolato).

Il composer rimane attivo per gli altri badge sidebar (questions, categories, users, quizzes, audit, registrations, question-reports) — sono conteggi globali cacheati 60s e non hanno l'overhead per-utente del contatore notifiche. Il contatore notifiche ora vive nel solo `NotificationBell`, che è renderizzato una volta sola dal layout e si aggiorna via polling.

### Dispatch nei Service

Nessuna modifica: i dispatch nei Service erano già corretti dalla 3.1. Il canale `database` è applicato automaticamente perché incluso in `via()` su ogni Notification.

### Copertura test

`tests/Feature/NotificationsTest.php` — 22 test, 77 asserzioni (tutti verdi). Oltre ai 19 test ereditati dalla 3.1 (dispatch, fan-out admin, motivazione, fire-and-forget, payload `toDatabase()`, pagina `/notifications` index/destroy/destroyAll con 403 cross-user, bell Livewire `unreadCount` + `markAllAsRead`), aggiunti 3 test dedicati al flow campanella e delete singolo:

- `test_notification_bell_mark_as_read_marks_single_and_redirects_to_payload_url` — chiama `markAsRead($id)` su una singola notifica, verifica che venga marcata come letta, che il redirect punti al `url` del payload (es. `dashboard` per `RegistrazioneApprovataNotification`) e che le altre notifiche restino non lette.
- `test_notification_bell_mark_as_read_ignores_notifications_of_other_users` — un viewer autenticato non può marcare come letta una notifica di un altro viewer (la query `$user->notifications()->whereKey($id)->first()` filtra per `notifiable_id`, restituisce `null`, il metodo esce senza redirect). Verifica `assertNoRedirect()` e che `read_at` rimanga `null`.
- `test_destroy_removes_a_single_owned_notification` — happy path del `DELETE /notifications/{id}` da parte del proprietario: redirect con flash `success`, record rimosso dalla tabella, le altre notifiche dell'utente restano.

### Files

```
app/
  Http/Controllers/NotificationController.php       # index() / destroy() / destroyAll()
  Http/Livewire/NotificationBell.php                # componente campanella + polling
  Notifications/*.php                               # toDatabase() su tutte le 11 classi
  Providers/AppServiceProvider.php                  # rimosso $unreadNotifications + case 'notifications'
config/
  adminlte.php                                      # voce sidebar "Notifiche" senza label_color
database/migrations/
  2026_05_17_161328_create_notifications_table.php  # UUID PK + morph notifiable
resources/views/
  layouts/admin.blade.php                           # <livewire:notification-bell /> in content_top_nav_right
  livewire/notification-bell.blade.php              # dropdown AdminLTE 3 + polling 30s
  notifications/index.blade.php                     # lista paginata + delete per riga + delete-all
routes/
  web.php                                           # gruppo notifications.* (auth)
tests/Feature/
  NotificationsTest.php                             # +3 test (markAsRead singola, cross-user, destroy)
```

---

## [Unreleased] — Feature 3.1: notifiche email iscrizioni

Stato della feature al 2026-05-20: l'infrastruttura email + queue per il workflow iscrizioni risulta integralmente implementata in iterazioni precedenti. Questa entry documenta retroattivamente lo stato attuale (nessun codice nuovo introdotto da questa task — solo verifica e tracciamento).

### Infrastruttura queue

- **Driver queue**: `database`. Migration `0001_01_01_000002_create_jobs_table.php` crea le tabelle `jobs`, `job_batches`, `failed_jobs`. Le notifiche del workflow iscrizioni sono accodate sulla queue `emails`; il worker locale si lancia con `php artisan queue:work --queue=emails`.
- **`.env.example`** già contiene `QUEUE_CONNECTION=database` (con commento operativo sul worker) e il blocco SMTP Mailtrap (`MAIL_MAILER`, `MAIL_HOST=sandbox.smtp.mailtrap.io`, `MAIL_PORT=2525`, `MAIL_USERNAME`, `MAIL_PASSWORD`, `MAIL_ENCRYPTION=tls`, `MAIL_FROM_ADDRESS`, `MAIL_FROM_NAME`).

### Notification class (`app/Notifications/`)

Nomenclatura italiana coerente con la convenzione del progetto. Tutte le classi: `implements ShouldQueue`, `use Queueable`, costruttore con `$this->onQueue('emails')`, canali `['mail', 'database']`, template Markdown in `resources/views/emails/`.

Workflow anagrafica viewer:

- **`RegistrazioneApprovataNotification`** — al viewer quando l'admin approva l'anagrafica (mapping spec: `ViewerProfileApproved`).
- **`RegistrazioneRifiutataNotification(?string $motivazione)`** — al viewer quando l'admin rifiuta, con motivo (mapping spec: `ViewerProfileRejected`).
- **`AnagraficaModificataNotification(User $viewer)`** — agli admin quando il viewer reinvia l'anagrafica dopo un'approvazione precedente (mapping spec: `ViewerProfileResubmitted`).
- **`NuovaRichiestaAnagraficaNotification(User $viewer)`** — agli admin al primo invio della richiesta.

Workflow iscrizioni quiz:

- **`IscrizioneQuizApprovataNotification(Quiz $quiz)`** — al viewer quando l'admin approva (mapping spec: `EnrollmentApproved`).
- **`IscrizioneQuizRifiutataNotification(Quiz $quiz, ?string $motivazione)`** — al viewer quando l'admin rifiuta, con motivo (mapping spec: `EnrollmentRejected`).
- **`IscrizioneQuizRiapertaNotification(Quiz $quiz)`** — al viewer quando l'admin riapre un'iscrizione (mapping spec: `EnrollmentReopened`).
- **`NuovaIscrizioneQuizNotification(User $viewer, Quiz $quiz)`** — agli admin quando un viewer richiede l'iscrizione (mapping spec: `NewEnrollmentRequest`).

Notification correlate fuori dallo scope 3.1 ma già presenti: `QuizConfermatoNotification`, `QuizEsameCompletatoNotification`, `RuoloAggiornatoNotification`.

### Template Markdown (`resources/views/emails/`)

`registrazione-approvata`, `registrazione-rifiutata`, `anagrafica-modificata`, `nuova-richiesta-anagrafica`, `iscrizione-quiz-approvata`, `iscrizione-quiz-rifiutata`, `iscrizione-quiz-riaperta`, `nuova-iscrizione-quiz`, `quiz-confermato`, `quiz-esame-completato`, `ruolo-aggiornato`. Contengono saluto col nome anagrafico, descrizione dell'evento, motivo dove pertinente, link contestuale, firma.

### Dispatch nei Service

- **`app/Services/NotificationService.php`** — wrapper fire-and-forget: `send(mixed $notifiables, Notification $notification)` e `sendToAdmins(Notification $notification)` racchiudono `NotificationFacade::send()` in try/catch + `Log::warning()` per evitare che un errore di dispatch propaghi nel workflow utente. `sendToAdmins()` recupera gli utenti con `role = ROLE_ADMIN`.
- **`app/Services/UserRegistrationService.php`** — `submit()` invia `NuovaRichiestaAnagrafica` (primo invio) o `AnagraficaModificata` (reinvio post-approvazione) agli admin; `approve()` invia `RegistrazioneApprovata` al viewer; `reject($reason)` invia `RegistrazioneRifiutata($reason)` al viewer.
- **`app/Services/QuizEnrollmentService.php`** — `request()` invia `NuovaIscrizioneQuiz` agli admin; `approve()` invia `IscrizioneQuizApprovata` al viewer; `reject($reason)` invia `IscrizioneQuizRifiutata($reason)` al viewer; `reopen()` invia `IscrizioneQuizRiaperta` al viewer; `markCompleted()` invia `QuizEsameCompletato` agli admin (fuori scope 3.1).

Zero dispatch nei controller, in linea con la convenzione di progetto.

### Copertura test

`tests/Feature/NotificationsTest.php` — 19 test, 67 asserzioni, tutti passanti. Coprono: dispatch su ogni transizione, fan-out agli admin, motivazione preservata, fire-and-forget (la redirect avviene anche se il dispatch lancia), payload del canale database, route name resolvibili.

### Scostamenti rispetto alla spec di Feature 3.1

- **Nomenclatura**: il progetto usa nomi italiani (`Registrazione*`, `Iscrizione*`) anziché inglesi (`ViewerProfile*`, `Enrollment*`). Allineato alla convenzione preesistente delle altre Notification del codebase.
- **Canale database già attivo**: la spec 3.1 limitava a `mail`, prevedendo `database` per 3.2. Le classi correnti hanno già `['mail', 'database']` con `toDatabase()` implementato — di fatto parte di 3.2 è anticipata qui.
- **Fan-out limitato agli admin**: `NotificationService::sendToAdmins()` notifica solo `role = admin`, non gli editor. Il test `test_viewer_submitting_registration_notifies_admins` documenta esplicitamente questa scelta. La spec chiedeva admin **+ editor** per `ViewerProfileResubmitted` e `NewEnrollmentRequest`: punto aperto da chiarire prima dell'eventuale estensione.

---

## [2026-05-19] — Refactoring seeder domande e categorie

### Changed

- **`CategorySeeder`** — le categorie non sono più hardcodate in PHP: vengono lette dinamicamente dal foglio `Categorie` del file `storage/app/imports/file_con_category_id.xlsx` (colonne `category_name` / `category_id`). Lo slug viene generato con `Str::slug()`. L'aggiunta o rinomina di una categoria richiede solo la modifica dell'Excel, senza toccare il seeder.
- **`QuestionProductionSeeder` rinominato in `QuestionSeeder`** — rimosso il suffisso `Production` per allinearsi al naming standard; il seeder è già l'unico usato in tutti i contesti (sviluppo e produzione). Aggiornati i riferimenti in `DatabaseSeeder` e `ProductionSeeder`.
- **`QuestionSeeder`** — usa ora `category_id` direttamente dalla colonna D del foglio `Domande` invece di costruire una mappa nome→id con `Category::pluck()`. Eliminati `$typoFixes` (workaround per typo nell'Excel precedente) e `$categoryMap` (query non più necessaria). Il seeder non dipende più dall'ordine di esecuzione di `CategorySeeder` né dai nomi testuali delle categorie. Path file aggiornato a `storage/app/imports/file_con_category_id.xlsx`.
- **`ProductionSeeder`** — rimosso il docblock descrittivo (informazioni spostate nel README).
- **README** — aggiunto box `> Prerequisito` nella sezione `### 4. Database e dati iniziali` con il percorso atteso del file Excel e la struttura dei due fogli.

### Removed

- `database/seeders/QuestionProductionSeeder.php` — sostituito da `QuestionSeeder.php`.

### Files

```
database/seeders/
  CategorySeeder.php            # legge dal foglio "Categorie" dell'Excel
  QuestionSeeder.php            # nuovo (era QuestionProductionSeeder)
  QuestionProductionSeeder.php  # rimosso
  DatabaseSeeder.php            # →QuestionSeeder
  ProductionSeeder.php          # →QuestionSeeder, rimosso docblock
README.md                       # prerequisito file Excel nella sezione installazione
```

---

## [2026-05-19] — Import listato MIT (patente B)

### Added

- **`config/mit_import.php`** — file di configurazione centrale per l'import: mappatura colonne Excel → campi interni (chiavi stringa per file con header, indici numerici per file senza), mappa argomenti MIT 1-25 → nomi categoria DB (ricerca con `str_contains` case-insensitive), lista `true_values` accettati come risposta vera (`v`, `vero`, `1`, `true`, `s`, `si`, `sì`), `max_rows` (10 000) e `max_file_size_kb` (10 240). Modificare questo file è sufficiente per adattare l'import a qualsiasi variante del listato MIT senza toccare PHP.
- **Migration `2026_05_19_200001_add_mit_code_to_questions_table`** — aggiunge `mit_code` (`string(20)`, nullable, unique) e `mit_image_code` (`string(50)`, nullable) alla tabella `questions`. `mit_code` è il codice univoco MIT (es. `"B001-001"`) usato per la deduplicazione; `mit_image_code` persiste il nome del file immagine distribuito dal MIT come metadato per la futura associazione tramite Media Manager. `down()` elimina l'indice unique e le due colonne senza toccare i record esistenti.
- **`app/Services/MitImportService.php`** — service principale con metodo `import(string $filePath, bool $dryRun, bool $updateExisting, ?int $topicFilter, ?callable $onProgress)`. Flusso: lettura sheet con un'istanza anonima `ToArray` (maatwebsite/excel), rimozione header e rimappatura chiavi (opzionale), pre-load in memoria di tutte le categorie e di tutti i `mit_code` esistenti (zero N+1), costruzione `topicMap[topicCode → categoryId]` via `buildTopicMap()`. Per ogni riga: validazione (testo non vuoto, argomento mappato), normalizzazione risposta (case-insensitive, `true_values`), deduplicazione prioritaria per `mit_code` poi per `(question, category_id)`. La flag `updateExisting` controlla se i duplicati vengono aggiornati o saltati; `topicFilter` limita l'import a un singolo argomento. Tutto avviene dentro `DB::beginTransaction()` / `DB::commit()` (rollback su `$dryRun = true`). Restituisce un oggetto con `imported`, `updated`, `skipped`, `errors`.
- **`app/Console/Commands/ImportMitQuestions.php`** — comando `questions:import-mit {file} {--dry-run} {--update-existing} {--topic=}`. Pre-start: verifica esistenza file, mostra tabella configurazione colonne con `$this->table()`. Esegue il service con progress bar indeterminata (`createProgressBar()`) + callback `onProgress`. A fine import: tabella riepilogo, lista errori riga per riga, durata in secondi, `Log::info()` senza PII. Exit code `SUCCESS` se zero errori, `FAILURE` altrimenti.
- **`app/Http/Requests/ImportMitQuestionsRequest.php`** — FormRequest per il POST web. `authorize()` → `canCreateQuestion()` (coerente con l'import generico esistente). Regole: `file` required + mimes:`xlsx,xls,csv` + `max:config('mit_import.max_file_size_kb')`, `update_existing` boolean, `topic_filter` nullable integer 1-25, `dry_run` boolean.
- **`resources/views/admin/questions/mit-import.blade.php`** — pagina admin `sg-wrapper-sm`. Header con breadcrumb "Domande > Import MIT" e pulsante "Indietro". Sezione errori import (da `session('mit_import_errors')`) con lista scrollabile (max 300px). Accordion Alpine `x-data="{ open: false }"` che mostra la configurazione attiva (tabella colonne + tabella topic_map — utile per verificare prima dell'upload). Form upload: input file `.xlsx/.xls/.csv` con feedback errore inline, select argomento MIT (opzionale), checkbox "Aggiorna domande esistenti" e checkbox "Dry run". Pulsante "Avvia import" + link "Annulla" + indicazione `config/mit_import.php` in fondo.
- **Pulsante "Import MIT"** nella view lista domande admin (`admin/questions/index.blade.php`) — aggiunto accanto agli altri pulsanti header (Nuova, Export, Template) inside il guard `canCreateQuestion()`.
- **`tests/Feature/MitImportTest.php`** — 23 test (43 asserzioni). Copertura: import valido con persistenza DB, deduplicazione `mit_code` default skip / `--update-existing` update, argomento non mappato saltato con errore, testo vuoto saltato con errore, 6 data provider per normalizzazione risposta vera (`V`/`VERO`/`1`/`TRUE`/`v`/`vero`), 4 data provider per risposta falsa (`F`/`FALSO`/`0`/`FALSE`), dry-run rollback senza record, `--topic` filtra per argomento, POST HTTP con redirect e flash `success`, POST senza file → validazione, POST con file oltre limite → validazione (verifica fix known issue), viewer → 403, invariante `imported + updated + skipped = righe totali`. Fixture Excel create in-memory con `PhpOffice\PhpSpreadsheet` (già dipendenza di maatwebsite/excel).

### Changed

- **`app/Models/Question.php`** — aggiunti `mit_code` e `mit_image_code` a `$fillable`; aggiunto scope `scopeFromMit($query)` che filtra le domande con `mit_code` non nullo.
- **`app/Http/Controllers/QuestionController.php`** — aggiunti metodi `showMitImport(): View` e `storeMitImport(ImportMitQuestionsRequest, MitImportService): RedirectResponse`. La logica di business è interamente nel service; il controller si occupa solo dello store temporaneo del file (`store('tmp/mit-import')`), del dispatch e della pulizia del file temporaneo (`Storage::delete`). Usa `Storage::disk('local')->path()` invece di `storage_path()` per compatibilità con i test (`Storage::fake('local')`).
- **`routes/web.php`** — due nuove route nel gruppo `role:admin,editor,viewer`: `GET admin/questions/mit-import` → `showMitImport` (name: `admin.questions.mit-import`) e `POST admin/questions/mit-import` → `storeMitImport` (name: `admin.questions.mit-import.store`), dichiarate prima di `Route::resource('questions')` per evitare conflitti con le rotte resource.

### Fixed

- **`app/Http/Requests/ImportQuestionsRequest.php`** — **fix known issue**: aggiunto `max:5120` alla validazione del file Excel nell'import generico (era assente, segnalato nei Known issues del CHANGELOG precedente). Coperto dal test #13 di `MitImportTest`.

### Files

```
app/
  Console/Commands/ImportMitQuestions.php           # nuovo comando artisan
  Http/Controllers/QuestionController.php           # +showMitImport(), +storeMitImport()
  Http/Requests/ImportMitQuestionsRequest.php       # nuovo FormRequest
  Http/Requests/ImportQuestionsRequest.php          # +max:5120 (fix known issue)
  Models/Question.php                               # +mit_code, +mit_image_code in $fillable, +scopeFromMit
  Services/MitImportService.php                     # nuovo service (parse, dedup, dry-run)
config/
  mit_import.php                                    # nuovo file di configurazione
database/migrations/
  2026_05_19_200001_add_mit_code_to_questions_table.php
resources/views/admin/questions/
  index.blade.php                                   # +pulsante "Import MIT"
  mit-import.blade.php                              # nuova pagina
routes/
  web.php                                           # +2 route admin.questions.mit-import.*
tests/Feature/
  MitImportTest.php                                 # 23 test, 43 asserzioni
```

---

## [2026-05-19] — Segnalazione errori nelle domande

### Added

- **Tabella `question_reports`** (migration `2026_05_19_100000`) — `question_id` + `user_id` con `cascadeOnDelete` (eliminando una domanda o un utente i report relativi spariscono; coerente con `gdpr:anonymize`), `body` (text, max 1000 char), `type` enum (`risposta_errata`, `testo_ambiguo`, `immagine_mancante`, `contenuto_obsoleto`, `altro`), `status` enum (`pending`/`accepted`/`rejected`, default `pending`), `admin_note` (text, nullable), `resolved_by` (FK `users` `nullOnDelete`), `resolved_at` (timestamp nullable). Indici su `(status, created_at)` e `(question_id, status)` per le query del pannello admin.
- **Model `App\Models\QuestionReport`** — `$fillable` completo, cast `resolved_at => datetime`, costanti `STATUS_*`, helper statici `types()` e `statuses()` per le UI, scope `pending()` / `accepted()` / `rejected()`, relazioni `question()`, `user()`, `resolvedBy()`. Factory `QuestionReportFactory` per i test (default status `pending`).
- **Relazioni** — `Question::reports()` e `Question::pendingReports()` (scope chained); `User::questionReports()`.
- **Componente Livewire `ReportButton`** (`app/Http/Livewire/ReportButton.php`) — riceve `$questionId`, due property pubbliche di stato (`open`, `submitted`), `type` + `body` con `#[Validate]` attribute (Livewire 3). Metodi: `toggleForm()` (apre/chiude form con reset stato), `sendReport()` (validazione + anti-spam max 3 pending dello stesso viewer sulla stessa domanda + create + reset), `setCurrentQuestion(int $id)` con `#[On('report-button-set-question')]` per consentire alle view play JS-driven (`quiz/play`, `simulator/play`) di ri-targettare la domanda corrente senza re-mount. Nome metodo `sendReport()` (non `submit`) scelto per evitare collisione con i magic name della Proxy `$wire`.
- **View `resources/views/livewire/report-button.blade.php`** — pulsante `btn-sm btn-outline-warning` con icona `fas fa-flag`, label "Segnala" nascosta sotto md; form collassabile via `@if($open)` (puro Livewire, senza Alpine) con select tipo, textarea `maxlength=1000`, errori inline `@error`, due bottoni "Invia segnalazione" / "Annulla" con `wire:loading` mirato (`wire:target`). Visibile solo `@auth + isViewer()`.
- **`<livewire:report-button>` inserito in 4 view play**:
  - `resources/views/study/play.blade.php` — affianco al `BookmarkButton` nel footer navigazione (`ms-2`).
  - `resources/views/quiz/attempt.blade.php` — in fondo a ogni card domanda, allineato a destra con `d-flex justify-content-end gap-2` insieme al `BookmarkButton`.
  - `resources/views/quiz/play.blade.php` — dentro la `question-card` dopo il `#feedback`, montato con `:question-id="$questionsJson[0]['id'] ?? 0"`. La funzione `renderQuestion()` dispatcha `Livewire.dispatch('report-button-set-question', { id: q.id })` ad ogni cambio domanda (3 righe aggiunte, autosave/feedback invariati).
  - `resources/views/simulator/play.blade.php` — stesso pattern del quiz play, posizionato sotto i pulsanti Precedente/Prossima.
- **`app/Http/Controllers/Admin/QuestionReportController.php`** — 5 metodi (`index`, `show`, `accept`, `reject`, `destroy`), autorizzazione `abort_unless(auth()->user()->canEditQuestion(), 403)` su tutti. `index()` con filtri GET (`status`, `type`, `question_id`), eager-load `with(['question:id,question,category_id', 'user:id,name,email', 'resolvedBy:id,name'])` (no N+1), paginazione 20 con `withQueryString()`, restituisce anche `$stats` con i 3 conteggi pending/accepted/rejected. `accept()` / `reject()` validano `admin_note` (nullable, max 1000), settano `status`, `admin_note`, `resolved_by = auth()->id()`, `resolved_at = now()` e redirigono all'index con flash `success`.
- **Route admin** in `routes/web.php` — gruppo `admin/question-reports` (dentro il middleware `role:admin,editor,viewer`, l'autorizzazione fine-grained è nel controller): `GET /` (`index`), `GET /{report}` (`show`), `PATCH /{report}/accept` (`accept`), `PATCH /{report}/reject` (`reject`), `DELETE /{report}` (`destroy`). Name prefix `admin.question-reports.*`.
- **`resources/views/admin/question-reports/index.blade.php`** — 3 `small-box` AdminLTE in cima (pending arancione / accepted verde / rejected grigio) con link "Filtra" che applica `?status=…`; barra filtri form GET con select stato/tipo e input ID domanda; tabella `sg-table` con ID, domanda troncata a 60 char, tipo (badge `bg-info`), segnalante (nome + email), data, stato (badge colorato), pulsante "Dettaglio". Riga con `table-warning` per i pending. Paginazione standard.
- **`resources/views/admin/question-reports/show.blade.php`** — layout 2 colonne (`col-md-7` / `col-md-5`). Sinistra: card domanda con badge categoria, testo, immagine via `Storage::url()`, badge risposta corretta `VERO`/`FALSO`, link "Modifica domanda" verso `admin.questions.edit`. Destra: card dettagli (segnalante con email, data, tipo, stato) + alert con il testo del report; se già risolto mostra anche risolutore/timestamp/nota. Form di gestione (visibile solo se `status === 'pending'`): textarea Alpine `x-model="note"` con valore propagato a 3 form separati (accept/reject/destroy) tramite `:value="note"`. Pulsanti Bootstrap nativi, `onsubmit="return confirm()"` sul destroy.
- **Voce sidebar "Segnalazioni"** in `config/adminlte.php` — icona `fas fa-flag`, gate nuovo `view-question-reports`, key `question-reports`, posizionata subito sotto "Domande" nella sezione *CATALOGO*.
- **Gate `view-question-reports`** in `AppServiceProvider::boot()` — risolve a `$user->canEditQuestion()` (admin via bypass + editor con permesso `edit_question`).
- **Badge sidebar con contatore report pending** — nel view composer di `AppServiceProvider`: aggiunta chiave `pending_reports` alla cache `admin_badges` (`QuestionReport::pending()->count()`, **senza** filtro temporale `$since` perché i report sono pochi e sempre actionable, non "novità"). Nuovo `case 'question-reports'` nello `switch` del menu: badge colore `warning`, visibile solo se > 0.
- **`tests/Feature/QuestionReportTest.php`** — 13 test (41 asserzioni): invio Livewire valido con persistenza DB, validazione `body` (min 10) e `type` (enum), anti-spam (4° report pending bloccato), index admin accessibile a admin/editor con `edit_question` e 403 per viewer, accept con `resolved_by`/`resolved_at`/`admin_note` corretti, reject simmetrico, destroy con riga rimossa, KPI `$stats` corretti, cascade delete su `Question`, view show senza form di gestione per report già risolto.

### Files

```
app/
  Http/Controllers/Admin/QuestionReportController.php   # nuovo controller (5 action)
  Http/Livewire/ReportButton.php                        # nuovo componente Livewire
  Models/QuestionReport.php                             # nuovo model + scope + factory
  Models/Question.php                                   # +reports(), +pendingReports()
  Models/User.php                                       # +questionReports()
  Providers/AppServiceProvider.php                      # +Gate view-question-reports, +badge pending_reports
config/
  adminlte.php                                          # +voce sidebar "Segnalazioni"
database/
  factories/QuestionReportFactory.php                   # nuovo factory
  migrations/2026_05_19_100000_create_question_reports_table.php
resources/views/
  admin/question-reports/index.blade.php                # KPI + filtri + tabella
  admin/question-reports/show.blade.php                 # 2 colonne + form gestione
  livewire/report-button.blade.php                      # pulsante + form Alpine collapse
  quiz/play.blade.php                                   # +<livewire:report-button> + dispatch JS
  quiz/attempt.blade.php                                # +<livewire:report-button> per card
  simulator/play.blade.php                              # +<livewire:report-button> + dispatch JS
  study/play.blade.php                                  # +<livewire:report-button> in footer
routes/
  web.php                                               # +gruppo admin.question-reports.*
tests/Feature/
  QuestionReportTest.php                                # 13 test, 41 asserzioni
```

---

## [2026-05-19] — Simulatore Esame Reale (patente B)

### Added

- **`config/simulator.php`** — formato esame ufficiale vigente dal 20/12/2021 (DM MIT 27/10/2021): `questions = 30`, `time_limit = 20` minuti, `max_errors = 3`. Mappa `distribution`: 12 categorie fondamentali × 2 domande + 6 integrative × 1 = 30 domande. I nomi categoria sono confrontati con `LOWER(name) LIKE '%nome%'` per resistere a piccole differenze ortografiche; categorie mancanti vengono saltate con `Log::warning()`. Eventuale gap rispetto al target di 30 è coperto da domande casuali extra (con log esplicito).
- **`app/Services/SimulatorService.php`** — `buildQuestionList()` estrae le domande secondo distribuzione + shuffle finale; `startSession()` crea un `QuizAttempt` con `quiz_id = null` e salva `simulator_questions` / `simulator_attempt_id` in sessione; `updateAttempt()` ricostruisce la mappa `question_id => is_true` da `Question::whereIn($ids)` senza dipendere dal `Quiz` (perché `quiz_id` è null); `getResultDetail()` costruisce KPI e righe della view risultato con criterio **promosso se `wrong + not_answered ≤ max_errors`** (criterio reale MIT, non 60%).
- **`app/Http/Controllers/SimulatorController.php`** — `index`, `start`, `play`, `autosave`, `submit`, `result`, `destroy`. Controllo cross-user esplicito in `autosave` e `result` (`$attempt->user_id !== auth()->id() → 403`).
- **`GET /simulator`** (`simulator.index`) — pagina introduttiva con tre `info-box` AdminLTE (30 domande / 20 min / 3 errori) e pulsante "Inizia simulazione".
- **`GET /simulator/play`** (`simulator.play`) — view replicata strutturalmente da `quiz/play.blade.php` (timer JS, navigatore sidebar, error-dots, autosave debounced 1s) con tre differenze: pulsanti **Precedente** / **Prossima** sempre visibili sotto le risposte (navigazione libera tipica esame reale); pulsante **"Abbandona"** in alto a destra (`btn-outline-danger`) che fa `DELETE /simulator/session`; modal Bootstrap di **conferma consegna** con riepilogo risposte date/non date/errori prima del submit definitivo.
- **`GET /simulator/result/{attempt}`** (`simulator.result`) — view dedicata `simulator/result.blade.php` con badge **PROMOSSO** / **NON SUPERATO** (criterio reale: max errori), 6 KPI, barra di progresso e lista domanda per domanda con risposta utente vs corretta.
- **`PUT /simulator/{attempt}/autosave`** + **`POST /simulator/submit`** + **`DELETE /simulator/session`** — endpoint dedicati che non passano da `QuizAttemptService` (`updateAttempt` e `getAttemptDetail` dipendono da `$attempt->quiz->questions`, che esplode con `quiz_id = null`).
- **Voce sidebar "Simulatore esame"** in `config/adminlte.php` — icona `fas fa-stopwatch`, gate `exam-participant`, posizionata sotto "Modalità Studio" nella sezione *STUDIO*.
- **Migration `make_quiz_id_nullable_in_quiz_attempts_table`** (`2026_05_19_000001`) — `quiz_id` nullable per consentire i tentativi del simulatore non legati a un quiz preesistente.
- **`tests/Feature/SimulatorTest.php`** — 13 test (49 asserzioni): accesso autenticato/anonimo, start con pool valido e con DB vuoto, play con/senza sessione attiva, autosave con ricalcolo score + protezione cross-user, submit + redirect risultato, destroy sessione, view risultato per owner e blocco cross-user, log warning su categoria inesistente in distribuzione, `withDefault` su `QuizAttempt::quiz` quando `quiz_id` è null.

### Changed

- **`app/Models/QuizAttempt.php`** — `quiz()` ora usa `withDefault(['title' => 'Simulatore Esame'])` per evitare NPE nelle view condivise quando `quiz_id` è null (tentativi del simulatore).
- **`routes/web.php`** — gruppo `simulator.*` con 7 route (`index`, `start`, `play`, `autosave`, `submit`, `result`, `destroy`) nel middleware `auth`.

### Files

```
app/
  Http/Controllers/SimulatorController.php       # nuovo controller
  Models/QuizAttempt.php                         # quiz()->withDefault()
  Services/SimulatorService.php                  # nuovo service
config/
  adminlte.php                                   # +voce "Simulatore esame"
  simulator.php                                  # nuovo: parametri esame + distribuzione
database/migrations/
  2026_05_19_000001_make_quiz_id_nullable...     # quiz_id nullable
resources/views/simulator/
  index.blade.php                                # pagina introduttiva
  play.blade.php                                 # view di gioco
  result.blade.php                               # view risultato dedicata
routes/
  web.php                                        # +gruppo simulator.*
tests/Feature/
  SimulatorTest.php                              # 13 test, 49 asserzioni
```

---

## [2026-05-19] — Calendario sessioni d'esame

### Added

- **Scopes Eloquent su `Quiz`**: `scopeEnrollmentsOpen`, `scopeEnrollmentsUpcoming`, `scopeEnrollmentsClosed` — query builder riutilizzabili per filtrare i quiz confermati in base alla finestra di iscrizione. Compatibili con MySQL e SQLite (nessun raw SQL).
- **Accessor `enrollment_status` su `Quiz`**: restituisce `'open'` / `'upcoming'` / `'closed'` calcolato a runtime dalle date, pronto per le view senza logica condizionale inline.
- **`GET /calendar`** — pagina calendario sessioni (`CalendarController::index()`): carica le tre collection (`$upcoming`, `$open`, `$closed`) con query separate senza N+1, recupera gli ID iscrizioni dell'utente corrente e la variabile `$canEnroll` con la stessa logica del catalogo quiz confermati.
- **`resources/views/calendar/index.blade.php`** — lista cronologica divisa in tre card con bordo colorato (arancio/verde/grigio): *Prossime sessioni*, *Iscrizioni aperte*, *Sessioni chiuse* (ultime 10). Countdown Alpine.js via `@push('js')` per quiz `upcoming` (decorativo, zero dipendenze aggiuntive).
- **`resources/views/calendar/_quiz-row.blade.php`** — partial riusato nelle tre sezioni; mostra date apertura/chiusura, badge stato iscrizioni, badge "Già iscritto", pulsante "Richiedi iscrizione" (logica di visibilità copiata esattamente dal catalogo `quiz/confirmed/index.blade.php`: `$canEnroll` + finestra aperta + nessuna iscrizione esistente) o "Completa profilo" se il viewer non è ancora approvato.
- **Widget "Prossima sessione" nella dashboard viewer** — `info-box` AdminLTE `bg-gradient-success` inserita in `stats/dashboard.blade.php` prima delle statistiche; mostra il titolo del quiz più vicino tra `enrollmentsOpen()` e `enrollmentsUpcoming()` con link al calendario. Visibile solo nella vista personale (`!$isAdminView`).
- **Route `GET /calendar`** (`name: calendar.index`) nel gruppo `auth` di `routes/web.php`.
- **Voce sidebar "Calendario sessioni"** in `config/adminlte.php` — icona `fas fa-calendar-alt`, gate `viewer-quiz-area`, posizionata dopo "Quiz disponibili" nella sezione *ESAMI UFFICIALI*.
- **`tests/Feature/CalendarTest.php`** — 16 test (34 asserzioni): accesso autenticato (200) e anonimo (redirect login), quiz nelle sezioni corrette per ogni combinazione di date, quiz senza date → sezione open, badge "Già iscritto", assenza pulsante iscrizione per quiz upcoming/closed e per viewer non approvato, 4 test unitari sull'accessor `enrollment_status`, widget dashboard con quiz esistente e con scelta tra open e upcoming.

### Changed

- `app/Http/Controllers/UserStatsController::me()` — aggiunta query `$nextSession` (doppia query `enrollmentsOpen` / `enrollmentsUpcoming` senza `orderByRaw` per compatibilità SQLite) passata alla view `stats.dashboard`.

### Files

```
app/
  Http/Controllers/CalendarController.php          # nuovo controller
  Http/Controllers/UserStatsController.php          # +$nextSession per la dashboard viewer
  Models/Quiz.php                                   # +scopeEnrollmentsOpen/Upcoming/Closed, +getEnrollmentStatusAttribute
config/
  adminlte.php                                      # +voce "Calendario sessioni"
resources/views/
  calendar/index.blade.php                          # nuova pagina
  calendar/_quiz-row.blade.php                      # nuovo partial
  stats/dashboard.blade.php                         # +widget "Prossima sessione"
routes/
  web.php                                           # +Route::get('/calendar', ...)
tests/Feature/
  CalendarTest.php                                  # 16 test, 34 asserzioni
```

---

## [2026-05-19] — Bookmark domande persistente

### Added

- **Tabella pivot `question_user_bookmarks`** (migration `2026_05_18_000001`) — `user_id`, `question_id`, `note` (nullable, max 500 char), timestamps. Constraint unique su `(user_id, question_id)`; `cascadeOnDelete` su entrambe le FK: eliminando un utente i suoi bookmark spariscono automaticamente (compatibile con `gdpr:anonymize`).
- **Relazione `User::bookmarkedQuestions(): BelongsToMany`** — con `withPivot('note')`, `withTimestamps()`, `orderByPivot('created_at', 'desc')`.
- **Relazione `Question::bookmarkedBy(): BelongsToMany`** — con `withPivot('note')`, `withTimestamps()`.
- **Componente Livewire `BookmarkButton`** (`app/Http/Livewire/BookmarkButton.php`) — riceve `$questionId`, gestisce toggle (usando `BelongsToMany::toggle()`) e salvataggio nota sul pivot. Solo per viewer autenticati; admin/editor non vedono il pulsante. Property `$noteInput` (con `#[Validate('nullable|string|max:500')]`) separata da `$note` (valore salvato visualizzato); `wire:model.blur="noteInput"` sulla textarea; `wire:target` scoped su ogni bottone per spinner precisi. UI: pulsante `btn-sm btn-outline-secondary` / `btn-warning` con icona `far`/`fas fa-bookmark`; nota collassabile via Alpine `x-data`/`x-show`/`x-transition` con `saveNote()` a chiamata esplicita.
- **`GET /bookmarks`** — pagina "Domande salvate" (`BookmarkController::index()`): filtri GET per categoria e testo, paginazione 20/pagina, card per ogni domanda con categoria/data salvataggio/risposta corretta/nota/immagine. Empty state con link a Modalità Studio. In cima (se ci sono bookmark) pulsante "Studia le domande salvate" che avvia la sessione studio con `source=bookmarks`.
- **`DELETE /bookmarks/{question}`** — rimozione bookmark (`BookmarkController::destroy()`): 403 se la domanda non è nel bookmark dell'utente autenticato (protezione cross-user).
- **Sorgente `bookmarks` in Modalità Studio** (`StudyService::SOURCE_BOOKMARKS`) — `questionsFromBookmarks()` preleva gli ID da `auth()->user()->bookmarkedQuestions()->pluck('questions.id')`. Se la lista è vuota, `StudyController::start()` reindirizza a `GET /bookmarks` con flash `warning` invece della generica `back()->with('error')`.
- **Voce sidebar "Domande salvate"** in `config/adminlte.php` — icona `fas fa-bookmark`, gate `exam-participant` (solo viewer), posizionata sotto "Modalità Studio" nella sezione *STUDIO*.
- **Pulsante bookmark in `quiz/attempt.blade.php`** — in fondo a ogni card domanda nella revisione post-quiz, allineato a destra, visibile solo ai viewer.
- **Pulsante bookmark in `study/play.blade.php`** — accanto al pulsante "Segna da ripassare" nel footer navigazione. Rimosso il caricamento CDN Alpine ridondante: `@livewireScripts` (presente nel layout) include già Alpine 3; la funzione `studyPlay()` non richiede modifiche.
- **Test** (`tests/Feature/BookmarkTest.php`, 15 test, 33 asserzioni): toggle add/remove, constraint unique, isolamento dati tra utenti, `destroy` 200 e 403 cross-user, avvio studio da bookmarks, redirect con warning se bookmarks vuoti, cascade delete su eliminazione utente, accesso 200 per viewer autenticato, redirect login per unauthenticated, filtro per categoria, filtro per testo libero, `saveNote()` via `Livewire::test()` con verifica pivot DB, validazione `noteInput > 500` caratteri.

---

## [2026-05-18] — Pagina dettaglio tentativo (revisione domande)

### Added

- **Pagina dettaglio tentativo** (`GET /quiz/attempts/{id}`) — riscritta completamente con revisione domanda per domanda. Struttura: card riepilogo verde (`card-success`) se promosso, rossa (`card-danger`) se rimandato, con 6 KPI (punteggio, percentuale, errori/max, non risposto, durata, data) e barra di progresso Bootstrap; una card per ogni domanda ordinata per `position` (con `_pivot_index` come fallback) con bordo colorato `card-outline card-success/danger/warning`, badge categoria, testo domanda, immagine opzionale via `Storage::url()`, risposta utente vs corretta, tempo speso discreto. Banner `alert-info` quando un admin visualizza il tentativo di un altro utente.
- **`QuizAttemptService::getAttemptDetail(QuizAttempt): array`** — costruisce la collection senza N+1 (domande caricate in una singola query via relationship, categorie tramite `Question::$with`), calcola i KPI incluso `passed = errori ≤ quiz.max_errors`, formatta la durata in `"X min Y sec"`. Ritorna l'array completo con `attempt`, `quiz`, `stats`, `questions` pronto per la view.
- **Test** (`tests/Feature/QuizTest.php`) — 5 nuovi test: viewer vede il proprio tentativo, viewer bloccato (403) sul tentativo altrui, admin bypass IDOR, calcolo KPI (`correct`/`wrong`/`not_answered`/`passed`) con dati nel formato esteso, `assertSee('PROMOSSO')` e `assertSee('RIMANDATO')` in base a `max_errors`.

---

## [2026-05-18] — GDPR, Comandi utili, UI responsive e fix badge sidebar

### Added

- **Comandi Artisan GDPR** (`app/Console/Commands/GdprAnonymize.php`, `GdprList.php`) — vedi sezione GDPR nel README per la descrizione completa.
- **Pannello admin "Comandi utili"** (`GET /admin/commands`, solo `admin`) — vedi sezione dedicata nel README.

### Fixed

- **Badge sidebar nascosti quando a zero** — `AppServiceProvider` view composer: i contatori `questions`, `categories`, `quizzes`, `users`, `audit` ora compaiono solo se il valore è `> 0`, allineando il comportamento ai contatori `registrations` e `notifications` che già applicavano questa logica.
- **UI responsive mobile** — audit responsive su viste admin (`admin/questions`, `admin/quizzes`, `admin/users`, `admin/audit`), quiz (`quiz/play`, `quiz/attempt`, `quiz/attempts`), studio (`study/play`, `study/summary`) e profilo (`profile/edit`): rimossi stili inline incompatibili con schermi piccoli, sostituiti con classi utility `sg-*` e breakpoint Bootstrap.

### Changed

- **Sidebar** — miglioramento estetico voci e sezioni; voce "Comandi utili" aggiunta nella sezione SISTEMA (`fas fa-terminal`).

---

## [Unreleased] — Refactor cumulativo (sicurezza, pulizia, evoluzione formato answers)

### Added
- **Comandi Artisan GDPR** — anonimizzazione e visibilità dei dati personali dei viewer:
  - `php artisan gdpr:anonymize {user_id} [--dry-run]` (`app/Console/Commands/GdprAnonymize.php`): anonimizza tutta la PII di un viewer nella tabella `users` (`name` → `"Utente Anonimo {id}"`, `email` → `"anonimo-{id}@eliminato.invalid"` su dominio RFC 2606, `password` rihashata con stringa random da 64 char per bloccare il login, e azzeramento di `first_name`/`last_name`/`address`/`birth_date`/`birth_place`/`fiscal_code`/`id_document_path`/`email_verified_at`/`remember_token`/tutti i campi `registration_*`). Le scritture passano da `DB::table('users')->update()` per bypassare il cast `'hashed'` (eviterebbe il doppio hash). Elimina il file fisico del documento dal disk `public` (`registrations/...`), le `notifications` morph-bound all'utente e — quando `session.driver === 'database'` — le righe in `sessions` con `user_id` matching (altrimenti warn esplicito che il driver file/redis va invalidato manualmente). Tutto dentro `DB::transaction()` con rollback in caso di eccezione. Protezioni: utente inesistente → exit code 1; ruolo `admin` → blocco esplicito + exit code 1; `--dry-run` mostra il piano senza scrivere nulla. `Log::info()` finale con `user_id`/`executor`/`timestamp`/contatori, **senza** PII pre-anonimizzazione. Quiz_attempts e quiz_enrollments restano intoccati (statistiche aggregate / link a utente anonimo).
  - `php artisan gdpr:list` (`app/Console/Commands/GdprList.php`): tabella Artisan con tutti i viewer e colonna "Anonimizzato" (Sì/No basata sul dominio `@eliminato.invalid`), eager load `withCount('quizAttempts')` per evitare N+1.
  - Test in `tests/Feature/GdprTest.php` (7 test, 40 asserzioni): copertura PII anonimizzata + documento eliminato + notifiche svuotate, blocco admin, ID inesistente, dry-run no-op verificato per email/fiscal_code/storage/notifiche, login impossibile post-anonimizzazione su entrambe le email (vecchia e nuova), chiusura sessioni DB, marker corretto in `gdpr:list`.

- **Pannello admin "Comandi utili"** (`GET /admin/commands`, solo `admin`) — pagina dedicata con tile + pulsante "Esegui" per una whitelist di comandi `php artisan`, organizzati in quattro gruppi:
  - *Code*: `queue:work --queue=emails --stop-when-empty --tries=3`, `queue:work --stop-when-empty --tries=3`, `queue:failed`, `queue:retry all`, `queue:flush` (distruttivo, dietro `confirm()` JS).
  - *Cache*: `cache:clear`, `config:clear`, `route:clear`, `view:clear`, `optimize:clear`.
  - *Sistema*: `migrate:status`, `storage:link`, `about`.
  - *GDPR*: `gdpr:list` (elenco viewer con marker anonimizzati), `gdpr:anonymize {id} --dry-run` (simulazione), `gdpr:anonymize {id}` (definitivo, distruttivo, dietro `confirm()` JS). Gli ultimi due ricevono `user_id` da un input number nella tile, validato lato server (`required|integer|min:1`).

  Esecuzione sincrona via `Artisan::call()` con cattura output, exit code e durata; il risultato dell'ultimo comando è mostrato in cima alla pagina (comando ricostruito, exit code, durata in ms, output integrale in `<pre>`). I comandi long-running come `queue:work` usano sempre `--stop-when-empty` per garantire la terminazione entro la request HTTP — la pagina non lancia daemon. Whitelist nella costante `CommandController::COMMANDS`: lo slug è validato (404 se non in whitelist); gli input runtime sono dichiarati per-comando nella chiave `inputs` con tipo/validation rules e mappatura `arg` verso l'argomento Artisan, validati prima di `Artisan::call()` (no shell, no input arbitrario). Gate `admin-only` su `index()` e `run()`. Nuova voce menu "Comandi utili" (`fas fa-terminal`) nella sezione SISTEMA.
- **Evoluzione formato `QuizAttempt.answers`** (migration non-distruttiva `2026_05_17_220000_migrate_quiz_attempts_answers_to_extended_format`): il campo JSON passa dal formato flat `{ "12": 1 }` al formato esteso `{ "12": { "correct": 1, "answered_at": <unix>, "time_spent_seconds": null, "position": 1 } }`. La migration converte i record esistenti con `lazy()` (nessuna memory spike); il `down()` ripristina il formato flat. Campi per risposta: `correct` (0|1, obbligatorio), `answered_at` (Unix timestamp), `time_spent_seconds` (nullable), `position` (posizione nella sequenza, nullable).
- **`QuizAttempt::getAnswerResult(int|string $questionId): ?int`** — punto di accesso unico al risultato di una singola risposta. Gestisce sia il formato esteso sia il flat legacy; restituisce `null` se la domanda non ha risposta.
- **Anteprima ingrandita immagine domanda** in `/admin/questions`: cliccando sulla miniatura nella DataTable si apre un modal Bootstrap con l'immagine a piena dimensione (max 500×500). Il titolo del modal mostra il **testo integrale della domanda** (passato via attributo `data-question` sul tag `<img>` da `QuestionsDataTable`), in modo che anche le domande troncate a 50 caratteri nella colonna "Domanda" siano leggibili per esteso senza tooltip.

### Changed
- **Voci menu esami ufficiali — visibilità per ruolo**: admin ed editor non partecipano agli esami ufficiali, quindi non devono vedere "Le mie iscrizioni" e "I miei tentativi" nel menu (sono dati personali del viewer). Il catalogo "Quiz disponibili" resta invece visibile anche ad admin/editor in **sola lettura**, per consentire la consultazione del catalogo ufficiale.
  - Gate `viewer-quiz-area` (`app/Providers/AppServiceProvider.php`) estesa anche all'editor (prima: viewer + admin). Controlla l'header `esami` e la voce "Quiz disponibili".
  - Nuova gate `exam-participant` (solo viewer). Applicata in `config/adminlte.php` alle voci "Le mie iscrizioni" e "I miei tentativi".
  - `resources/views/quiz/confirmed/index.blade.php` reso "read-only" per i non-viewer: banner informativo *"Visualizzazione in sola lettura. Gli utenti amministratori/editor non partecipano agli esami ufficiali."*, colonne "Stato iscrizione" e "Azioni" nascoste, alert "iscrizione anagrafica necessaria" mostrato solo al viewer.
- **`QuizAttemptService`** — `scoreAnswers()` gestisce ora sia il formato esteso (`$answer['correct']`) sia il flat legacy (`(int) $answer`). Aggiunto metodo privato `normalizeAnswers()` che converte flat → esteso prima del salvataggio, castando i tipi (empty string da jQuery form encoding → `null` per i campi nullable). `record()` e `updateAttempt()` chiamano `normalizeAnswers()` prima di ogni write su DB.
- **`StoreQuizAttemptRequest` e `UpdateQuizAttemptRequest`** — sostituita la regola `answers.* => 'in:0,1'` (flat) con quattro regole `answers.*.correct | answered_at | time_spent_seconds | position`, tutte con `sometimes` per accettare entrambi i formati durante la transizione.
- **JS `quiz/play.blade.php`** — l'oggetto risposta inviato all'autosave e al submit passa da `answers[id] = value` al formato esteso `{ correct, answered_at: Math.floor(Date.now()/1000), time_spent_seconds: null, position: currentIndex + 1 }`. Aggiornate le comparazioni nel navigatore e nel calcolo errori da `answers[id] === q.correct` a `answers[id].correct === q.correct`.
- **Filtro Vero/Falso nascosto ai viewer** in `/admin/questions`: la `<select id="filter-is-true">` è ora dentro `@if(!auth()->user()->isViewer())`. Coerente con la già esistente esclusione delle colonne "Risposta" e "Azioni" dalla DataTable per il ruolo viewer, che non deve poter filtrare per la risposta corretta.

### Security
- **Fix IDOR su `GET /quiz/attempts/{id}`**: qualsiasi utente autenticato poteva consultare il dettaglio dei tentativi altrui cambiando l'ID nell'URL. Aggiunto controllo di ownership in `QuizAttemptController::show()` (admin/canEditUser/proprietario)
- **Fix autorizzazione su API gestione domande del quiz**: `addQuestion`, `removeQuestion` e `reorder` su `QuizController` erano accessibili a qualsiasi utente con ruolo `viewer` (gruppo rotta `role:admin,editor,viewer`). Aggiunto `abort_unless(canEditQuiz(), 403)` su tutti e tre

### Fixed
- **Badge sidebar nascosti quando a zero** — i contatori nel menu laterale (Domande, Categorie, Utenti, Quiz, Audit Log) vengono ora mostrati **solo quando il valore è > 0**; prima comparivano sempre, anche mostrando "0". `Registrations` e `Notifications` già applicavano questa logica; uniformato il comportamento su tutti i casi (`AppServiceProvider` — view composer dei badge).
- **N+1 query in `QuizService::calculateScore()`** (poi rimosso): `Question::find()` dentro il `foreach` sostituita con singola `Question::whereIn()->pluck()`
- **Lazy-load in `QuizAttemptController::show()`**: aggiunto `$attempt->loadMissing('quiz')` per evitare query lazy nella view dopo il fix IDOR
- **Lazy-load in `QuizAttemptService::record()`**: passato direttamente l'oggetto `$enrollment` già risolto a `markCompleted()`, evitando il lazy load via `$attempt->enrollment`
- **Link hardcoded a `route('quiz.play', 1)`** nelle view sostituiti con `route('quiz.confirmed.index')` (unico entry point superstite per scegliere un quiz)
- **Anti-pattern `Category::all()` inline** in `admin/quizzes/questions.blade.php`: sostituito con `$categories` passato dal controller

### Changed
- **`QuestionController::index()` ottimizzato**: rimossa la query inutile `Question::with('category')->get()` (la tabella è popolata via AJAX da `/questions/data`). Ora la rotta passa solo `$categories`
- **`QuizController::manageQuestions()` ottimizzato**: rimossa la query `Question::with('category')->get()` non utilizzata; aggiunta `$categories` per il filtro del select
- **Empty-state dashboard utente**: il CTA "Inizia un quiz" ora porta al catalogo dei quiz confermati invece che al quiz random rimosso

### Removed
- **Sistema quiz random viewer-side** rimosso interamente (decisione di prodotto: creare un quiz normale è veloce, non serve l'esercitazione random):
  - rotta `quiz.random` (`GET /quiz/random-play`)
  - `QuizController::randomPlay()` + `QuizService::startRandomPlay()`
  - tutti i link `route('quiz.random')` nelle view (`quiz/attempts.blade.php`, `quiz/attempt.blade.php`, `stats/dashboard.blade.php`)
  - le funzionalità admin `createRandom`/`fillRandom` (rotte `admin.quizzes.random` e `admin.quizzes.fillRandom`) sono **mantenute** — creazione di quiz reali con domande random
- **Sistema legacy `QuizResult`** rimosso interamente (sostituito dal flusso `/quiz/attempts`):
  - model `app/Models/QuizResult.php`
  - `QuizController::submit()` + `QuizController::results()` + `QuizService::calculateScore()`
  - rotte `quiz.submit` e `quiz.results`
  - view `resources/views/quiz/results.blade.php`
  - **Nota:** la migration `2026_04_26_140117_create_quiz_results_table.php` NON è stata eliminata (distruttivo per gli ambienti già migrati); creare una migration `drop_quiz_results_table` in PR separata
  - `tests/Feature/QuizTest.php` riscritto sul nuovo flusso (3 test, 10 asserzioni, tutti verdi)
- **`app/Filters/QuestionFilter.php`** + cartella `app/Filters/` — diventata orfana dopo la rimozione della query inutile in `QuestionController::index()`; il filtraggio domande è gestito da `QuestionsDataTable`
- `User::canEdit()` — mai chiamato; sostituito funzionalmente dai metodi specifici `canEditQuestion/Quiz/Category/User()`
- `Quiz::generateRandom()` — mai chiamato; la generazione random è gestita da `QuizService::createRandom()`
- `app/View/Components/AppLayout.php` — componente `<x-app-layout>` residuo dello scaffolding Breeze, mai usato
- Commento di debug `// dd(...)` rimasto in `RoleMiddleware`

### Tests
- **`tests/Feature/QuizTest.php`** — i 3 test adattati al formato `answers` esteso (da `[$q->id => 1]` a `[$q->id => ['correct' => 1, 'answered_at' => null, ...]]`). Suite: 3 test, 10 asserzioni, tutti verdi.
- Proposta una batteria di **65 nuovi test** organizzati in 9 aree funzionali (Quiz CRUD/state machine, gameplay, QuizAttempt, QuizEnrollment, Domande, Categorie, Utenti, Ruoli/Permessi, Dashboard/Ricerca) — vedi `REFACTOR_REPORT_ARCHITECT.md` per il dettaglio
- `QuizTest.php` riscritto sul nuovo flusso `/quiz/attempts` (POST per creare il tentativo, PUT per aggiornare/calcolare lo score). Suite completa: 70 test, 188 asserzioni, tutti verdi

### Known issues / Segnalati ma non risolti
- `View::composer('*', ...)` in `AppServiceProvider` gira su ogni view (anche nested): preferire binding a `layouts.admin`
- ~~`ImportQuestionsRequest` non valida il limite di dimensione del file Excel (`max:5120`)~~ — **risolto** in `[2026-05-19] Import listato MIT`
- `Quiz::hasQuestion()` e `QuizAttemptService::scoreAnswers()` senza type hint
- `RoleMiddleware::handle()` senza return type `Response`
- Migration `drop_quiz_results_table` da creare in PR separata per dismettere fisicamente la tabella `quiz_results`

## [2026-05-17] — Area Admin — Operatività

### Added

- **Export Excel risultati quiz confermati** (`GET /admin/quizzes/{quiz}/export-results`) — nuova classe `App\Exports\QuizResultsExport` (basata su `FromQuery` + `WithHeadings` + `WithMapping` + `WithStyles` per evitare di caricare tutta la collection in memoria). Una riga per iscritto (`approved` o `completed`), colonne: `Cognome | Nome | Email | Data tentativo | Punteggio | Totale domande | Percentuale | Esito | Durata (min)`. Esito derivato da `max_errors` del quiz (Promosso se `errori <= max_errors`); chi non ha ancora svolto compare con le colonne vuote ed esito "Non svolto"; durata convertita da secondi a minuti con un decimale. Ordinamento `COALESCE(NULLIF(last_name,''), name) ASC, first_name ASC`. Nome file scaricato: `risultati-{slug-quiz}-{YYYY-MM-DD}.xlsx`. Autorizzazione: solo `admin` (`abort_unless` → 403) + 404 se il quiz non è confermato.

- **Pannello riepilogo per quiz confermato** (`GET /admin/quizzes/{quiz}/summary`) — pagina admin dedicata a ogni quiz confermato:
  - 4 `small-box` AdminLTE: Totale iscritti (approved+completed), Hanno completato (con `QuizAttempt`), Non ancora svolto, Punteggio medio (% con un decimale, solo su chi ha completato)
  - Tabella ordinata per Cognome ASC con colonne `Cognome | Nome | Email | Stato | Punteggio | Percentuale | Esito | Data tentativo`; righe colorate `table-success` (Promosso), `table-danger` (Rimandato), `table-warning` (Non svolto); badge stato coerenti con la palette AdminLTE (`warning|success|info|danger`)
  - Pulsante "Esporta Excel" in cima alla card che chiama la route della F1
  - Pulsante "Riepilogo" aggiunto nella lista quiz admin (`admin.quizzes.index`) accanto alla "Schedulazione", visibile solo per quiz `confirmed`
  - Logica isolata in `App\Services\QuizSummaryService::getSummary(Quiz $quiz)` con eager loading `enrollments.user` + `enrollments.quizAttempt` per evitare N+1

- **Schedulazione apertura/chiusura iscrizioni quiz** — nuova migration `add_enrollment_schedule_to_quizzes_table` con due colonne `timestamp` nullable: `enrollments_open_at` e `enrollments_close_at`. Comportamento sul catalogo viewer (`resources/views/quiz/confirmed/index.blade.php`):
  - se `enrollments_open_at` valorizzato e futuro → pulsante nascosto, messaggio "Iscrizioni aperte dal {data formattata in italiano via `translatedFormat`}"
  - se `enrollments_close_at` valorizzato e passato → pulsante nascosto, messaggio "Iscrizioni chiuse"
  - se entrambi `null` → comportamento invariato
  - Helper sul model `Quiz`: `enrollmentsNotYetOpen()`, `enrollmentsClosed()`, `enrollmentsCurrentlyOpen()`
  - Validazione server-side anche in `QuizEnrollmentService::request()` (oltre alla UI) per impedire iscrizioni fuori finestra anche via POST diretto

- **Form admin schedulazione** (`GET/PUT /admin/quizzes/{quiz}/schedule`) — nuova view `admin/quizzes/schedule.blade.php` con due campi `datetime-local` ("Apertura iscrizioni" / "Chiusura iscrizioni") in una card *Schedulazione iscrizioni*. Entrambi facoltativi. `UpdateQuizScheduleRequest` valida `enrollments_close_at > enrollments_open_at` (regola `after:` che si attiva solo se entrambi presenti). Aggiornamento delegato a `QuizService::updateSchedule()` (controller resta pulito). Pulsante "Schedulazione" nella `admin.quizzes.index` solo per quiz `confirmed`.

- **Comando schedulato `enrollments:close-expired`** (`App\Console\Commands\CloseExpiredEnrollments`) — trova i quiz `confirmed` con `enrollments_close_at <= now()` che hanno ancora iscrizioni `pending` e le sposta tutte in `rejected` (motivazione loggata: *"Iscrizioni scadute automaticamente"*). Non tocca le iscrizioni `approved` o `completed`. Ogni esecuzione logga via `Log::info()` con `quiz_id`, `quiz_title`, `closed_count`, `enrollments_close_at`. Registrato in `routes/console.php` con `Schedule::command('enrollments:close-expired')->dailyAt('00:05')`.

### Changed

- `Quiz` model: aggiunti `enrollments_open_at` e `enrollments_close_at` al `$fillable` e al `$casts` come `datetime`.
- `routes/console.php`: importato `Illuminate\Support\Facades\Schedule` e registrato l'esecuzione giornaliera del nuovo comando.

### Tests

- **`tests/Feature/AdminOperativityTest.php`** — 8 nuovi test (27 asserzioni) che coprono:
  - F1: l'admin scarica un `.xlsx` con nome `risultati-{slug}-{data}.xlsx` (verifica via `Excel::fake()` + `Excel::assertDownloaded()`); il viewer riceve 403
  - F2: KPI corretti con dati misti (completato promosso / completato rimandato / approved senza tentativo), la view risponde 200 all'admin e mostra i nominativi
  - F3: il comando `enrollments:close-expired` rifiuta SOLO le `pending` e lascia intatte `approved`/`completed`; salta i quiz con `close_at` futuro; la `UpdateQuizScheduleRequest` rifiuta `close_at < open_at` e accetta la finestra valida
- Suite completa: **114 test verdi, 340 asserzioni**.

### Files

```
app/
  Console/Commands/CloseExpiredEnrollments.php       # nuovo comando schedulato
  Exports/QuizResultsExport.php                       # FromQuery + WithHeadings + WithMapping + WithStyles
  Http/Controllers/QuizController.php                 # +exportResults, +summary, +editSchedule, +updateSchedule
  Http/Requests/UpdateQuizScheduleRequest.php         # nuovo FormRequest (after: open_at)
  Models/Quiz.php                                     # fillable/casts + helper enrollments*()
  Services/QuizEnrollmentService.php                  # gating server-side finestra iscrizioni
  Services/QuizService.php                            # +updateSchedule()
  Services/QuizSummaryService.php                     # nuovo service (KPI + iscritti arricchiti)
database/migrations/
  2026_05_17_*_add_enrollment_schedule_to_quizzes_table.php
resources/views/
  admin/quizzes/index.blade.php                       # +pulsanti Riepilogo/Schedulazione (solo confirmed)
  admin/quizzes/summary.blade.php                     # nuovo pannello riepilogo
  admin/quizzes/schedule.blade.php                    # nuovo form datetime-local
  quiz/confirmed/index.blade.php                      # gating "aperte dal …" / "chiuse"
routes/
  console.php                                         # Schedule::command(...)->dailyAt('00:05')
  web.php                                             # +3 route admin (summary, export-results, schedule.{edit,update})
tests/Feature/AdminOperativityTest.php                # nuovo file
```

## [2026-05-17] — Notifiche email & in-app

### Added
- **Sistema notifiche multi-canale** sul flusso iscrizioni, lifecycle quiz e amministrazione utenti. 11 classi `Notification` in `app/Notifications/`, ciascuna `via()` → `['mail', 'database']`:
  - *Anagrafica viewer*: `NuovaRichiestaAnagraficaNotification` (admin), `AnagraficaModificataNotification` (admin, su reinvio dopo approvazione), `RegistrazioneApprovataNotification` (viewer), `RegistrazioneRifiutataNotification` (viewer, con motivazione)
  - *Iscrizione quiz*: `NuovaIscrizioneQuizNotification` (admin), `IscrizioneQuizApprovataNotification` (viewer), `IscrizioneQuizRifiutataNotification` (viewer, motivazione opzionale), `IscrizioneQuizRiapertaNotification` (viewer), `QuizEsameCompletatoNotification` (admin, alla chiusura del tentativo)
  - *Lifecycle quiz*: `QuizConfermatoNotification` (broadcast ai viewer approvati alla conferma di un quiz ufficiale)
  - *Account*: `RuoloAggiornatoNotification` (utente, quando un admin cambia ruolo)
- **Canale email (Markdown mailables)** — template in `resources/views/emails/*.blade.php` con header, motivazione condizionale, CTA al portale e footer uniformi.
- **Canale database notifications** — migration `2026_05_17_*_create_notifications_table.php` (schema standard Laravel: UUID PK, polymorphic `notifiable`, `data` JSON con `title`/`body`/`url`/`icon`/`color`).
- **Bell Livewire in navbar** (`App\Http\Livewire\NotificationBell`) — contatore non-lette, dropdown delle ultime 10, `markAsRead` su click + redirect alla risorsa correlata, `markAllAsRead`. Integrata in `layouts.admin` via `@section('content_top_nav_right')`.
- **Pagina notifiche** `/notifications` (`NotificationController`) — paginazione, mark-as-read all'apertura, delete singolo (`DELETE /{id}`) e bulk delete (`DELETE /`).
- **Dispatch via queue `emails`** (driver `database`, già impostato in `.env.example`): le notifiche sono fire-and-forget e non bloccano la response. Worker in dev: `php artisan queue:work --queue=emails`.
- **Helper `App\Services\NotificationService`** — `send($notifiables, $notification)` / `sendToAdmins($notification)` con `try/catch` + `Log::warning`: se il dispatch fallisce (es. tabella `jobs` mancante, errore di serializzazione), l'eccezione viene loggata e il workflow utente prosegue.
- **Counter sidebar "ultima ora"** — i badge accanto a `Domande`, `Categorie`, `Quizzes`, `Utenti`, `Audit Log`, `Iscrizioni anagrafiche`, `Notifiche` mostrano solo gli elementi creati negli ultimi 60 minuti, non il totale. Logica nel View Composer di `AppServiceProvider`, cache `admin_badges` 60 s invalidata dagli Observer su create/update/delete; per le iscrizioni anagrafiche si usa `registration_submitted_at`.
- **Sidebar riorganizzata in 10 sezioni** per argomento: `AREA PERSONALE`, `STUDIO`, `ESAMI UFFICIALI`, `CATALOGO`, `QUIZ`, `ISCRIZIONI`, `ESITI & STATISTICHE`, `SISTEMA`, `UTENTI & RUOLI`, `ACCOUNT`. Ogni header espone una `can` coerente con le voci sottostanti per nascondere sezioni vuote ai ruoli senza permessi.
- **Test** `tests/Feature/NotificationsTest.php` — 19 test (67 asserzioni): 12 dispatch (`Notification::fake()` su ciascuno degli 11 eventi + 1 caso negativo "no role change → no notify"), 2 fallback con `shouldReceive('send')->andThrow()` che verificano che il workflow utente non si interrompa se il dispatch fallisce, 5 in-app feature (payload DB, pagina `/notifications`, 403 cross-user, scope `destroyAll`, counter Livewire bell).
- **README** — nuove sezioni "Email di notifica (Mailtrap)" (`### 6`), "Worker della coda email" (`### 7`), "Badge della sidebar — counter dell'ultima ora" (architettura tecnica), e descrizione del sistema notifiche.

### Changed
- `UserRegistrationService::submit/approve/reject` — accodano la notifica corrispondente. Il reinvio dopo `approved` invia `AnagraficaModificataNotification` (anziché `NuovaRichiestaAnagrafica`) per distinguere le revisioni dalle prime richieste.
- `QuizEnrollmentService::request/approve/reject/reopen/markCompleted` — accodano la notifica corrispondente. `reject(...)` accetta ora un parametro opzionale `?string $reason`.
- `QuizService::confirm` — notifica tutti i viewer approvati alla conferma di un quiz ufficiale.
- `UserService::update` — notifica l'utente quando il ruolo cambia, confrontando `$oldRole` vs `$user->fresh()->role`.
- `.env.example` — sezione `MAIL_*` configurata per Mailtrap (`smtp` / `sandbox.smtp.mailtrap.io`) con commento esplicativo; aggiunta nota su `QUEUE_CONNECTION=database` e coda `emails`.
- `routes/web.php` — aggiunto gruppo `notifications.*` (`index`/`destroy`/`destroyAll`) sotto middleware `auth`.
- `layouts/admin.blade.php` — bell Livewire iniettato in `@section('content_top_nav_right')` di AdminLTE.
- `lang/vendor/adminlte/it/menu.php` — nuove chiavi di traduzione per le 10 sezioni della sidebar (vecchie chiavi `contenuti`/`gestione_quiz`/`amministrazione` mantenute per retrocompatibilità).

### Technical notes
- **Fire-and-forget**: le notifiche `ShouldQueue` sulla coda `emails` non bloccano la response. Se SMTP è down o il worker è spento, gli utenti continuano a operare; le mail vengono spedite quando il worker torna attivo. Se il dispatch stesso fallisce (eccezione lato application), il `NotificationService` la logga e la swallowa.
- **Performance counter sidebar**: 6 query al cache miss (≤ 1/minuto/processo), `where('created_at', '>=', $since)` sfrutta l'indice di default; nessun indice aggiuntivo richiesto.

---

## [2026-05-17] — Modalità Studio, statistiche utente e seeder di produzione

### Added

- **Modalità Studio** (`GET /study`, `POST /study/start`, `GET /study/play`, `POST /study/flag/{question}`, `GET /study/summary`, `DELETE /study/session`) — allenamento libero senza timer né punteggio. `StudyService` gestisce la sessione interamente in PHP session (chiavi: `study_questions`, `study_index`, `study_flagged`, `study_answers`, `study_source`). Quattro sorgenti: quiz specifico (`published`/`confirmed`), categoria (ordine casuale), 30 domande casuali da tutto il database, domande marcate nella sessione precedente. Interfaccia Alpine.js con feedback inline immediato (nessun round-trip), navigazione avanti/indietro via `?index=N`, toggle segnalibro "da ripassare" via AJAX. Riepilogo finale con totale, risposte date, lista marcate e pulsante "Ripassa le marcate". 10 test in `tests/Feature/StudyTest.php`.

- **Dashboard personale e statistiche utente** (`GET /dashboard`, `POST /dashboard/{user}/refresh`, `GET /admin/users/{user}/stats`):
  - `UserStatsService` — calcola e cachea (`user_stats_{id}`, 10 min) le seguenti metriche: `total_attempts`, `total_correct`, `avg/best/worst_percentage`, `passed_count`, `failed_count`, `pass_rate`, `avg/total_duration`, `latest_attempts` (top 10), `daily_chart` (ultimi 30 gg), `avg_by_quiz` (top 10 per tentativi).
  - Cache invalidata automaticamente in `QuizAttempt::booted()` su `saved`/`deleted`. Il pulsante "Aggiorna ora" forza l'invalidazione via `UserStatsService::forget()`.
  - Grafici Chart.js: linea + barre (andamento 30 gg, doppio asse Y) e ciambella (esiti superati/non superati).
  - Admin e editor vedono la dashboard con KPI globali (`DashboardStatsService`); viewer vedono le proprie statistiche.
  - Protezione: un viewer non può accedere alle statistiche di un altro utente (403); admin e utenti con `canEditUser()` possono consultare qualsiasi profilo.
  - 9 test in `tests/Feature/UserStatsTest.php`.

- **Seeder domande reali** (`QuestionProductionSeeder`) — legge 7143 domande da un file Excel via `PhpSpreadsheet` e le importa nella tabella `questions` associando le 18 categorie della scuola guida (aggiornate in `CategorySeeder`). Usato dal `DatabaseSeeder` in luogo del seeder con domande fake.

- **Design system `sg-*`** — set di classi CSS utility (`sg-wrapper`, `sg-card`, `sg-btn`, `sg-stat-card`, `sg-badge`, `sg-table`, `sg-gap-*`, `sg-mt-*`, `sg-mb-*`, ecc.) che sostituisce gli stili inline nelle viste Blade, garantendo coerenza grafica e semplificando gli override responsive.

- **Adattamento UI admin per i viewer** — legenda stati quiz nella lista admin, filtro "Vero/Falso" nelle domande nascosto ai viewer (non devono poter filtrare per risposta corretta).

- **Permessi `read_*` e `bulk_*` configurabili dalla UI** — i permessi `read_question`, `read_quiz`, `read_category`, `read_user`, `bulk_question`, `bulk_quiz`, `bulk_category`, `bulk_user` sono ora gestibili dal pannello `Admin → Ruoli & Permessi` (`/admin/roles`).

- **Anteprima immagine domanda in modal** — nella DataTable delle domande admin, cliccando sulla miniatura si apre un modal Bootstrap con l'immagine a piena dimensione (max 500×500) e il testo integrale della domanda nell'header del modal (passato via `data-question` sull'`<img>` da `QuestionsDataTable`).

### Changed

- **`DashboardController`** reindirizziato: admin e editor che accedono a `/dashboard` vedono i KPI globali; i viewer vedono il proprio pannello statistiche. Separazione netta delle due viste (`admin.dashboard` vs `stats.dashboard`).
- **Riordino sidebar** — voci raggruppate in 10 sezioni semantiche (`AREA PERSONALE`, `STUDIO`, `ESAMI UFFICIALI`, `CATALOGO`, `QUIZ`, `ISCRIZIONI`, `ESITI & STATISTICHE`, `SISTEMA`, `UTENTI & RUOLI`, `ACCOUNT`).
- **Refactoring CSS** — eliminati stili inline complessi nelle view, sostituite con classi `sg-*`; rimossi i namespace inline da `web.php`.

---

## [2026-05-16] — Iscrizioni anagrafica, Dark Mode e Media Manager

### Added
- **Iscrizione anagrafica viewer** con approvazione admin: workflow completo `none → pending → approved/rejected`, form nel profilo con upload documento (PDF/JPG/PNG, max 5 MB), gestione approvazione/rifiuto con motivazione opzionale, stato visibile nel badge profilo (`registration-status-badge`).
- **Dark mode completo** — contrasto migliorato su tutte le viste; toggle nella navbar.
- **Media Manager Livewire** (`App\Http\Livewire\Admin\MediaManager`) — tab multi-cartella (`test`/`production`), griglia immagini con lazy load, upload (JPG/PNG/GIF/WEBP, max 2 MB), rinomina con aggiornamento referenze su `questions.image`, eliminazione con conteggio referenze. 8 test in `tests/Feature/MediaManagerTest.php`.
- **Branding** — favicon personalizzata, logo ScuolaGUIDA, rimozione riferimenti AdminLTE dalla UI.
- **Pagine errore personalizzate** — `401`, `403`, `404`, `500` con layout uniforme.

### Fixed
- Ricerca navbar apre risultati in nuova scheda (fix comportamento clic).
- Media Manager: rinominato `upload()` in `save()` per evitare conflitto con il metodo riservato di Livewire 3 (`$wire` proxy aliasa `upload` a una magic JS).

### Changed
- Dashboard e stats ridenominati: `/dashboard` è la homepage utente; `/admin/stats` è la panoramica admin.
- Riordino menu laterale con sezioni e separatori per ruolo.
- Test: aggiornato redirect atteso dopo login.

---

## [2026-05-15] — Iscrizioni quiz & gestione interfaccia

### Added
- **Iscrizioni ai quiz** (`QuizEnrollmentController`, `QuizEnrollmentService`) — workflow completo: richiesta viewer → approvazione/rifiuto admin → gioco → completamento. Stato: `pending → approved/rejected/completed`. Riapertura da parte dell'admin.
- **Pagine di errore personalizzate** — layout uniforme per 404, 401, 403, 500.

### Changed
- Refactoring interfaccia edit e manage quiz.
- Riordino menu laterale con sezioni e separatori per ruolo.

---

## [2026-05-14] — Refactoring business logic

### Added
- Permessi `read_xxx` e `bulk_xxx` per entità.
- Permessi granulari per ruolo in controller e viste admin.

### Changed
- **Refactor business logic** — logica estratta dai controller in Service (`QuizService`, `QuestionService`, `UserService`, `UserRegistrationService`, `SearchService`), FormRequest, Observer (`QuestionObserver`, `CategoryObserver`, `UserObserver`, `QuizObserver`) e DataTables (`QuestionsDataTable`).
- README: riscritto con istruzioni di installazione e documentazione del flusso business logic.

### Fixed
- DB seeder: risolti problemi di integrità dei dati.

---

## [2026-03-25] — Quiz Features & Search

### Added
- **Ricerca globale dalla navbar**: ricerca domande e categorie
- **Dark mode toggle**: pulsante nella navbar per attivare/disattivare dark mode
- Dashboard con statistiche per utente (con cache)
- **Design system unificato**: ispirato alla schermata quiz/play

### Fixed
- Disabilita CSRF middleware nei test per risolvere errori 419
- Fix logica play quiz: storico tentativi e restyling UI
- Fix permessi dashboard

---

## [2026-03-15] — Infrastructure & CI/CD

### Changed
- CI: consolida workflow di test in un unico file
- CI: allinea requisito PHP a 8.3
- Migrations: consolida in una per tabella

---

## [2026-03-01] — Initial Setup

### Added
- Setup iniziale del progetto Laravel con AdminLTE
- Autenticazione base
- Modelli e migrazioni principali (User, Quiz, Question, QuizAttempt)
- Controllers resource per gestione quiz
- Viste Blade template per admin e user

### Changed
- Dependencies: aggiorna composer packages all'ultima versione stabile
