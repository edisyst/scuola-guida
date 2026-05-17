# Changelog

Tutte le modifiche significative a questo progetto sono documentate in questo file.
Formato seguente [Keep a Changelog](https://keepachangelog.com/it/1.0.0/).

## [Unreleased] — Refactor

### Security
- **Fix IDOR su `GET /quiz/attempts/{id}`**: qualsiasi utente autenticato poteva consultare il dettaglio dei tentativi altrui cambiando l'ID nell'URL. Aggiunto controllo di ownership in `QuizAttemptController::show()` (admin/canEditUser/proprietario)
- **Fix autorizzazione su API gestione domande del quiz**: `addQuestion`, `removeQuestion` e `reorder` su `QuizController` erano accessibili a qualsiasi utente con ruolo `viewer` (gruppo rotta `role:admin,editor,viewer`). Aggiunto `abort_unless(canEditQuiz(), 403)` su tutti e tre

### Fixed
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
- Proposta una batteria di **65 nuovi test** organizzati in 9 aree funzionali (Quiz CRUD/state machine, gameplay, QuizAttempt, QuizEnrollment, Domande, Categorie, Utenti, Ruoli/Permessi, Dashboard/Ricerca) — vedi `REFACTOR_REPORT_ARCHITECT.md` per il dettaglio
- `QuizTest.php` riscritto sul nuovo flusso `/quiz/attempts` (POST per creare il tentativo, PUT per aggiornare/calcolare lo score). Suite completa: 70 test, 188 asserzioni, tutti verdi

### Known issues / Segnalati ma non risolti
- `View::composer('*', ...)` in `AppServiceProvider` gira su ogni view (anche nested): preferire binding a `layouts.admin`
- `ImportQuestionsRequest` non valida il limite di dimensione del file Excel (`max:5120`)
- `Quiz::hasQuestion()` e `QuizAttemptService::scoreAnswers()` senza type hint
- `RoleMiddleware::handle()` senza return type `Response`
- Migration `drop_quiz_results_table` da creare in PR separata per dismettere fisicamente la tabella `quiz_results`

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
- **Test** `tests/Feature/NotificationsTest.php` — 9 test (`Notification::fake()` su ogni evento + 2 fallback con `shouldReceive('send')->andThrow()` che verificano che il workflow utente non si interrompa se il dispatch fallisce).
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

## [2026-05-16] — Iscrizioni anagrafica

### Added
- **Iscrizione anagrafica viewer** con approvazione admin: nuova interfaccia per la gestione delle iscrizioni con workflow di approvazione

### Fixed
- Ricerca navbar apre risultati in nuova scheda (fix comportamento clic)

### Changed
- Test: aggiorna redirect atteso dopo login da admin

---

## [2026-05-10] — Dark Mode & Media Manager

### Added
- **Dark mode completo**: migliora contrasto su tutte le views, copertura completa

### Changed
- Media manager: spaziatura verticale, classi CSS mancanti, dark mode completa
- UI: migliora grafica media manager con spaziamento e dimensioni corretti
- Branding: favicon volante, logo ScuolaGUIDA, rimozione riferimenti AdminLTE
- README: aggiorna documentazione con funzionalità, architettura e ciclo di vita quiz attuali

### Fixed
- Media manager: rinomina `upload()` in `save()` per evitare alias riservato Livewire 3

---

## [2026-05-01] — Media Manager & Dashboard Refactor

### Added
- **Media manager completo**: tab multi-cartella, griglia immagini, gestione file separata dallo storage
- Seeder di produzione separato per domande reali

### Changed
- Rinomina dashboard e stats: `/dashboard` è la homepage utente, `/admin/stats` è la panoramica admin
- Riordino menu laterale con sezioni e separatori per ruolo
- Pagine di errore personalizzate (404, 401, 403, 500)

---

## [2026-04-20] — Iscrizioni & Quiz Management

### Added
- **Iscrizioni ai quiz**: gestione completa del workflow di iscrizione

### Changed
- Refactoring edit e manage quiz: migliora interfaccia e UX
- Fix interfaccia edit e manage quiz

---

## [2026-04-10] — Business Logic Refactor

### Changed
- **Refactor business logic**: estrai logica da controller in Services, FormRequests, Observers e DataTables
- README: riscrivi documentazione con istruzioni di installazione e flusso business logic

### Added
- Permessi `read_xxx` e `bulk_xxx` per entità
- Permessi granulari per ruolo in controller e viste admin

### Fixed
- Fix DB seeder: risolve problemi di integrità dei dati

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
