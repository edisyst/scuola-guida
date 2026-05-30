# Changelog

Tutte le modifiche significative a questo progetto sono documentate in questo file.
Formato seguente [Keep a Changelog](https://keepachangelog.com/it/1.0.0/).

---

## [Unreleased] — Feature 6.2: Versionamento domande e integrità storica dei tentativi

Snapshot immutabili di ogni domanda ad ogni modifica; i tentativi storici referenziano
la versione vista al momento della risposta e ne mostrano sempre il testo originale.
Semantica "snapshot del dopo": ogni versione conserva lo stato CORRENTE al momento
della creazione, così i tentativi puntano a ciò che il viewer ha effettivamente visto.
Data-migration idempotente crea la V1 per tutte le domande esistenti. Retrocompatibilità
completa: i tentativi pre-versionamento (senza question_version_id) fanno fallback al
Question corrente senza errori. Chiude il rischio di integrità storica aperto.

### Added

- `database/migrations/2026_05_30_100001_create_question_versions_table.php` — tabella
  `question_versions` con snapshot di `question`, `is_true`, `image`, `category_id`,
  `created_by` (nullable FK → users nullOnDelete), `created_at`; indice unico
  `(question_id, version_number)`; `cascadeOnDelete` su `question_id`.
- `database/migrations/2026_05_30_100002_seed_initial_question_versions.php` — data-migration
  idempotente che crea la V1 per tutte le domande esistenti senza versioni. `down()`
  rimuove solo le versioni `version_number=1, created_by=null` generate da questo script.
- `app/Models/QuestionVersion.php` — model immutabile (`UPDATED_AT = null`), relazioni
  `question()` e `creator()`, `scopeLatestVersion()`.
- `app/Services/QuestionVersionService.php` — `snapshotIfChanged()`: crea versione con
  stato corrente se almeno un campo versionabile è cambiato; `buildVersionMapForAttempt()`:
  carica in batch le versioni referenziate da un tentativo; `latestVersionIdMap()`: mappa
  `question_id → latest_version_id` per iniezione al momento della risposta;
  `restoreVersion()`: ripristina una versione storica creando un nuovo snapshot in cima;
  `isHistoricalVersion()`: confronto per il badge UI.
- `app/Http/Livewire/QuestionVersionHistory.php` — componente Livewire per lo storico
  versioni nella pagina edit admin: timeline con diff sintetico, modale read-only,
  ripristino con `wire:confirm`.
- `resources/views/livewire/question-version-history.blade.php` — view del componente.
- `tests/Feature/QuestionVersionTest.php` — 10 test: modifica testo crea versione,
  modifica campo non versionabile non crea versione, versione_id registrata nel tentativo,
  dettaglio tentativo mostra testo storico, revisione errori mostra testo storico,
  tentativo legacy (senza version_id) fallback senza errori, ripristino crea V3 senza
  cancellare V1/V2, data-migration idempotente, Livewire ripristino, accessori model.

### Changed

- `app/Models/Question.php` — aggiunte relazioni `versions()` (hasMany, desc), accessor
  `currentVersion()`, metodo `createVersion()` (snapshot del nuovo stato).
- `app/Models/QuizAttempt.php` — aggiunto `getAnswerVersionId(int|string): ?int`; il
  formato JSON `answers` è esteso con il campo `question_version_id` per ogni risposta.
- `app/Services/QuestionService.php` — inietta `QuestionVersionService`; `create()`
  crea V1 immediatamente; `update()` cattura gli attributi originali e chiama
  `snapshotIfChanged()` dopo l'aggiornamento.
- `app/Services/QuizAttemptService.php` — inietta `QuestionVersionService`; nuovo metodo
  privato `injectVersionIds()` che aggiunge `question_version_id` alle risposte normalizzate
  preservando i version_id già registrati (autosave idempotente); `getAttemptDetail()`
  usa `buildVersionMapForAttempt()` per fornire testo/risposta storica alle view con flag
  `is_historical`.
- `app/Services/ReviewErrorsService.php` — `getErrors()` traccia il `last_version_id`
  dall'ultimo tentativo sbagliato e carica le versioni in batch; la collection restituita
  include il campo `version` per ogni errore.
- `resources/views/quiz/attempt.blade.php` — usa `$item['version']` per testo e immagine;
  mostra badge "Versione storica" con tooltip quando la versione referenziata differisce
  dallo stato corrente.
- `resources/views/review-errors/index.blade.php` — mostra testo e risposta dalla versione
  storica quando disponibile; badge "Versione storica" con tooltip.
- `resources/views/admin/questions/edit.blade.php` — aggiunto componente
  `<livewire:question-version-history>` con card collassabile "Storico versioni".

---

## [Unreleased] — Feature 6.1: Reportistica avanzata con export PDF e confronto periodi

Sezione di reportistica aggregata admin con report mensili/trimestrali, export PDF tramite
`barryvdh/laravel-dompdf` e confronto con il periodo precedente. Aggregazioni scalari lato
DB (COUNT/AVG), lazy-loading delle risposte per metriche su domande, caching 24h su
periodi passati e 5 min su periodi correnti. 369/369 test verdi.

### Added

- `app/Services/ReportingService.php` — `buildPeriodReport()`: dataset aggregato su quiz
  confermati (total_attempts, active_students, pass_rate, average_score, outcomes_by_category,
  most_failed_questions, enrollments_count, attempts_per_day). `buildComparisonReport()`:
  calcola il periodo precedente di pari durata e i delta percentuali delle 4 metriche chiave.
  Cache 24h per periodi passati, 5 min per il periodo corrente.
- `app/Http/Controllers/Admin/ReportController.php` — metodi `index`, `show`, `exportPdf`.
  Autorizzazione `canEditQuiz()`, route sotto middleware `role:admin`.
- `app/Http/Requests/ReportFilterRequest.php` — validazione `from`, `to`, `preset`, `compare`.
- Route `GET /admin/reports`, `/admin/reports/show`, `/admin/reports/export-pdf` (nome
  `admin.reports.*`, gruppo `role:admin`).
- View `resources/views/admin/reports/index.blade.php` — form con preset rapidi
  (mese corrente/scorso, trimestre, anno), date picker, toggle confronto, export PDF.
- View `resources/views/admin/reports/show.blade.php` — small-box KPI, tabella confronto
  con frecce delta colorate, grafici Chart.js (trend lineare + bar orizzontale per categoria),
  tabella distribuzione esiti per categoria, tabella top 20 domande più sbagliate.
- Template PDF `resources/views/admin/reports/pdf/period.blade.php` — CSS inline
  compatibile dompdf, table-based layout, header/footer, metriche con delta, tabelle dati.
- Voce sidebar "Report" (`fas fa-chart-pie`, `can: admin-only`) nella sezione Esiti &
  Statistiche di `config/adminlte.php`.
- Dipendenza `barryvdh/laravel-dompdf ^3.1` aggiunta a `composer.json`.
- `tests/Feature/ReportingTest.php` — 12 test: calcolo pass_rate/average_score, conteggio
  studenti distinti, periodo precedente + delta, ordinamento top domande, caching, accessi
  403 viewer, HTTP admin (index/show/export-pdf), validazione date.

---

## [2026-05-29] — Refactor 5.7: Caching e ottimizzazione query

Sprint di ottimizzazione sistematica basato su `REPORT_CACHING_REVIEW.md`: migrazione
a Redis, caching su tutti i service computazionalmente costosi, rimozione di query N+1
e eager load implicito globale. Le query per page load dei viewer passano da ~20 a ~6–8.
357/357 test verdi.

### Changed

- Aggiunto `REPORT_CACHING_REVIEW.md` nella root: analisi sistematica di query globali,
  service candidati a cache, N+1 residui, contatori always-on (sidebar/topbar), infrastruttura
  cache e piano di 10 PR di ottimizzazione ordinate per ROI.
- PR-C1: migrato cache driver da `database` a `redis` (`predis/predis`); ogni cache hit
  non emette più query SQL sulla tabella `cache`. Aggiunto `REDIS_CACHE_DB=1` in `.env`.
- PR-C2: `SpacedRepetitionService::getUpcomingCount()` cached (TTL 300s, chiave
  `sr_upcoming_{user_id}`); invalidazione in `recordAnswer()`, `markAsLearned()`,
  `unmarkAsLearned()` — salva 4 query per ogni page load dei viewer sul layout admin.
- PR-C3: `DashboardStatsService::kpi()` cached (TTL 300s, chiave `dashboard_kpi`);
  `dailyCreated()` cached (TTL 900s, time-based). Invalidazione KPI in tutti e 4
  gli Observer (User/Question/Category/Quiz). Fix preesistente: `QuestionService::bulkDelete()`
  ora invalida esplicitamente entrambe le cache (il `whereIn()->delete()` bypassa gli Observer).
- PR-C4: `StreakService::getStats()` nuovo metodo cached (TTL dinamico fino a mezzanotte,
  chiave `streak_{user_id}`) che ritorna `{current, longest, has_today}` in un'unica voce;
  `recordActivity()` invalida la chiave. `UserStatsController::me()` usa `getStats()`
  al posto di 3 chiamate separate — elimina 3 query per ogni dashboard viewer.
- PR-C5: `ReviewErrorsService::getErrorCount()` cached (TTL 600s, chiave
  `review_errors_count_{user_id}`); invalida in `QuizAttemptService::record()`,
  `markAsLearned()`, `unmarkAsLearned()`. `UserStatsController::me()` usa `getErrorCount()`
  invece di `getErrors()->count()` — evita di caricare 20 QuizAttempt JSON per un solo intero.
- PR-C6: `SimulatorService::buildQuestionList()` pre-carica tutte le categorie con una query
  (`Category::select('id','name')->get()`) e risolve il lookup per nome in PHP con
  `str_contains` (stessa semantica del `LOWER(name) LIKE` originale). Da 18 query
  `Category::whereRaw` per ciclo a 1 query totale; risparmio ~17 query per ogni avvio simulatore.
- PR-C7: rimosso `Question::$with = ['category']`; aggiunto `->with('category')`
  esplicito nei 7 punti che usano `$question->category` (ReviewErrorsService×2,
  StudyService×2, SimulatorService::getResultDetail, QuizAttemptService::getAttemptDetail,
  DiagnosticTest::render). Gli altri 8 punti avevano già eager load esplicito o non
  accedono a category; non sono stati modificati.
- PR-C8: `BadgeService::checkAllBadges()` carica i badge guadagnati da cache (TTL
  1800s, chiave `earned_badges_{user_id}`, plain PHP array per serializzazione Redis
  affidabile). `awardIfEligible()` invalida la chiave ad ogni award. Salva 1–4 query
  ad ogni risposta durante lo studio per utenti che hanno già tutti i badge.
- PR-C9: `ReviewErrorsService::getLearnedCount()` conta direttamente su
  `learned_questions` con `COUNT(*)`. `ReviewErrorsController::index()` usa
  `getLearnedCount()` invece di `getLearned()->count()`, evitando di caricare
  tutti i Question model solo per ottenere un intero.
- PR-C10: `NotificationBell::loadNotifications()` cacha il conteggio non lette
  (TTL 30s, chiave `notif_unread_{user_id}`). `markAsRead()` e `markAllAsRead()`
  cancellano la chiave prima di ricaricare, garantendo freschezza immediata dopo
  azioni esplicite dell'utente.

### Fixed

- `DiagnosticFeatureTest::test_generate_questions_excludes_recently_seen_when_alternatives_exist`:
  test flaky perché `QuizAttemptFactory` imposta `created_at` a `now()->subDays(rand(0,30))`,
  portando il tentativo fuori dalla finestra di 24h di `recentlySeenQuestionIds()`. Aggiunto
  `created_at => now()` esplicito nel factory call del test.

---

## [2026-05-28] — Feature 5.6: PWA installabile e modalità offline-light

Trasforma l'applicazione in una PWA installabile (manifest + service worker) con supporto offline limitato alla modalità studio: domande pre-caricate in IndexedDB, risposte accodate localmente e sincronizzate al ritorno online, add-to-home-screen prompt discreto in dashboard, pagina offline elegante per tutte le altre rotte.

### Added

- **`public/manifest.json`** — Web App Manifest: `name` "ScuolaGUIDA — Quiz Patente", `short_name` "ScuolaGUIDA", `display: standalone`, `start_url: /dashboard`, `theme_color: #4361ee`, `orientation: portrait-primary`, icone SVG + PNG in multipli formati (192, 256, 384, 512 px) in `public/icons/`.

- **`public/icons/icon.svg`** — Icona vettoriale del volante su sfondo blu primario; sorgente da convertire in PNG con ImageMagick o Inkscape (istruzioni nel README).

- **`public/sw.js`** — Service worker con `CACHE_VERSION = 'sg-v1'`: install event pre-caching (`/offline`, manifest, icone), cache-first per asset Vite content-hashed (`/build/assets/**`), network-first con fallback `/offline` per navigazioni HTML, cache-first per altri asset statici. Non cachea mai POST/PUT/DELETE/PATCH, `/livewire/update`, `/admin/*`, `/2fa/*`. Cleanup vecchie cache nell'evento `activate`. Background sync handler che delega ai client via `postMessage`.

- **`resources/js/pwa.js`** — Registrazione del service worker su `DOMContentLoaded`; listener `beforeinstallprompt` che salva il prompt in `window.__pwaInstallPrompt` e dispatcha `CustomEvent('pwa:installable')`; listener `appinstalled` che pulisce il prompt.

- **`resources/js/offline-store.js`** — IndexedDB `scuolaguida_offline` v1: object store `questions` (keyPath `id`, indici su `category_id`, `last_fetched_at`), `categories` (keyPath `id`), `pending_answers` (autoIncrement, indice su `synced`). API esposta su `window.offlineStore`: `saveQuestions()`, `getAllQuestions()`, `getQuestionsByCategory()`, `getQuestionsCount()`, `enqueuePendingAnswer()`, `getPendingAnswers()`, `markAnswersSynced()`. Tutte le operazioni async/Promise; grazie alla guardia `if (!window.offlineStore)` l'app degrada silenziosamente se IndexedDB non è disponibile (Safari private).

- **`resources/views/offline.blade.php`** — Pagina offline standalone (no `@extends`, no CDN), CSS inline, icona WiFi-off SVG, bottone Riprova (`location.reload()`) e link "Vai alla modalità studio".

- **Route `GET /offline`** — Pubblica (no `auth`), cacheable dal SW, serve `offline.blade.php`.

- **`App\Http\Controllers\Api\OfflineController`** — Due endpoint JSON viewer-only: `GET /api/offline/questions` (ultime 100 domande revisionate via `question_reviews`, throttle `1,5`; eager load category) e `POST /api/offline/sync-answers` (itera array di risposte offline, chiama `SpacedRepetitionService::recordAnswer()` per ciascuna e `StreakService::recordActivity()` + `BadgeService::checkAllBadges()` una sola volta per sync, in DB transaction; restituisce `synced_ids`).

- **`App\Http\Requests\SyncAnswersRequest`** — Valida `answers[].id`, `answers[].question_id` (exists:questions), `answers[].user_answer` (in:0,1), `answers[].is_correct` (boolean), `answers[].answered_at` (date).

- **Vite entries** (`vite.config.js`) — Aggiunti `resources/js/pwa.js` e `resources/js/offline-store.js` come entry point separati; entrambi caricati via `@vite()` nelle view che ne hanno bisogno.

- **Meta tag PWA** (`layouts/admin.blade.php`) — `<link rel="manifest">`, `<meta name="theme-color">`, meta Apple (`apple-mobile-web-app-capable`, `apple-mobile-web-app-status-bar-style`, `apple-touch-icon`), `@vite(['resources/js/pwa.js'])` per la registrazione del SW su tutte le pagine admin.

- **Integrazione offline nella modalità studio** (`study/play.blade.php`) — Il componente Alpine `studyPlay()` viene esteso con: `init()` che prefetch via `/api/offline/questions` al caricamento online; `answer()` che, se `!navigator.onLine`, salva in `pending_answers` IndexedDB e mostra badge "Sei offline — risposta salvata"; `_enterOfflineMode()` che carica le domande dall'IDB e abilita la navigazione JS (`offlineNext()` / `offlinePrev()`); `_exitOfflineMode()` che on-reconnect chiama `_syncPendingAnswers()` e mostra toast con il conteggio sincronizzato. Il testo della domanda, il badge categoria e l'immagine sono resi reattivi ad Alpine per supportare lo swap offline. Il `@section('js')` include `@vite(['resources/js/offline-store.js'])`.

- **Banner add-to-home-screen** (`stats/dashboard.blade.php`) — Card Alpine.js visibile solo ai viewer non in standalone mode, con dismissal in `localStorage` per 7 giorni; pulsanti "Installa" (chiama `window.__pwaInstallPrompt.prompt()`) e "Non ora".

- **`tests/Feature/OfflineApiTest`** — 18 test: autenticazione e autorizzazione viewer-only su entrambi gli endpoint, throttle (200 poi 429), validazione question_id, mock di SpacedRepetitionService (chiamato per ogni risposta) e StreakService (chiamato una volta per sync), verifica scrittura in `question_reviews` e `user_activity_log`, test `synced_ids` nel response body, accessibilità pubblica di `/offline`.

### Changed

- **`resources/js/app.js`** — Aggiunto `import './pwa'` (per il bundle guest/auth; nel layout admin il caricamento avviene via `@vite` separato).

---

## [2026-05-27] — Feature 5.5: Gamification leggera — streak e badge

Gamification leggera per il viewer: streak giorni consecutivi di studio, badge per milestone (streak, domande risposte, primo simulatore promosso, completamento categorie), widget streak nella dashboard e notifica in-app al guadagno di un badge.

### Added

- **Migration `2026_05_27_130000_create_user_badges_table`** — tabella `user_badges` con `id`, `user_id` (FK cascadeOnDelete), `badge_code` (string 64), `earned_at` (timestamp), `metadata` (json nullable), timestamps; unique composito su `(user_id, badge_code)`. `down()` implementato.

- **Migration `2026_05_27_130001_create_user_activity_log_table`** — tabella `user_activity_log` con `id`, `user_id` (FK cascadeOnDelete), `activity_date` (date), `actions_count` (integer, default 1), timestamps; unique composito su `(user_id, activity_date)`. `down()` implementato.

- **`App\Models\UserBadge`** — model Eloquent con `HasFactory`; fillable: tutti i campi; cast `earned_at` → datetime, `metadata` → array; metodo `config()` per accedere al config del badge; relazione `user()`.

- **`App\Models\UserActivityLog`** — model Eloquent con `HasFactory`; fillable: `user_id`, `activity_date`, `actions_count`; relazione `user()`. Date memorizzate come stringhe `Y-m-d` (senza cast Eloquent per compatibilità SQLite).

- **`config/badges.php`** — mappa di 8 badge: `streak_7`, `streak_30`, `streak_100`, `questions_100`, `questions_500`, `questions_1000`, `first_pass`, `all_categories`; ogni voce ha `name`, `description`, `icon`, `color`. Nessun valore hardcoded nel codice.

- **`App\Services\StreakService`** — metodi: `recordActivity(User)` crea o incrementa il record giornaliero in `user_activity_log`; `getCurrentStreak(User): int` calcola la streak corrente (considera anche solo ieri se oggi assente); `getLongestStreak(User): int` calcola la streak storica massima.

- **`App\Services\BadgeService`** — iniezione di `StreakService`; metodi: `awardIfEligible(User, string, array): ?UserBadge` assegna il badge con idempotenza e dispatcha `BadgeEarned`; `checkAllBadges(User): array` controlla ed assegna tutti i badge eligibili con short-circuit sui già ottenuti.

- **`App\Notifications\BadgeEarned`** — canale solo `database`; payload `toDatabase()` con `title`, `body`, `url` → `viewer.profile.badges`, `icon`, `color` letti da `config('badges')`. Queued su `emails`.

- **`App\Http\Controllers\Viewer\ProfileBadgesController`** — metodo `index()` con `abort_unless(isViewer(), 403)`; recupera badge guadagnati + tutti i badge configurati; passa `currentStreak` e `longestStreak` alla view.

- **`resources/views/viewer/badges.blade.php`** — pagina "I miei badge": stat-card streak corrente/record/badge guadagnati, progress bar completamento, grid card badge (colorata se ottenuta, grigia se non ancora), counter "X / Y badge", empty state con CTA.

- **Widget streak dashboard** (`resources/views/stats/dashboard.blade.php`) — info-box `La tua streak` con icona fiamma, count giorni, migliore di sempre; warning "A rischio" se l'utente non ha ancora registrato attività oggi ma era attivo ieri; stato vuoto con messaggio motivazionale.

- **Route** (`routes/web.php`, middleware `auth`): `GET /profile/badges` → `viewer.profile.badges` → `ProfileBadgesController@index`.

- **Sidebar** (`config/adminlte.php`) — voce "I miei badge" (fas fa-award) nella sezione ACCOUNT, con gate `exam-participant`.

- **Hook `QuizAttemptService::record()`** — dopo ogni tentativo ufficiale (quiz confermato): chiama `StreakService::recordActivity` e `BadgeService::checkAllBadges`.

- **Hook `StudyController::flag()`** — dopo ogni risposta in modalità studio: chiama `StreakService::recordActivity` e `BadgeService::checkAllBadges`.

- **Hook `SimulatorController::submit()`** — dopo ogni simulatore: chiama `StreakService::recordActivity`; se promosso (`total_questions - score <= max_errors`) chiama `BadgeService::awardIfEligible(..., 'first_pass', ...)`; poi `BadgeService::checkAllBadges`.

- **`tests/Feature/GamificationTest`** — 23 test: `recordActivity` crea/incrementa il log, `getCurrentStreak` con/senza gap e senza attività oggi, `awardIfEligible` idempotenza + notifica, `checkAllBadges` per ogni tipo di badge, widget streak dashboard, pagina badge accessibile/bloccata.

---

## [2026-05-27] — Feature 5.4: Spaced repetition delle domande sbagliate

Algoritmo SM-2 che traccia automaticamente ogni risposta data in modalità studio e nei quiz, calcola l'intervallo ottimale di ripasso per ciascuna domanda e propone al viewer una sessione di ripasso ordinata per urgenza.

### Added

- **Migration `2026_05_27_120000_create_question_reviews_table`** — tabella `question_reviews` con colonne `id`, `user_id` (FK cascadeOnDelete), `question_id` (FK cascadeOnDelete), `next_review_at` (timestamp, index), `interval_days` (integer default 1), `ease_factor` (decimal 3,2 default 2.50), `repetitions` (integer default 0), `last_reviewed_at` (timestamp nullable), timestamps; unique composito su `(user_id, question_id)`; indice composito su `(user_id, next_review_at)`. `down()` implementato.

- **`App\Models\QuestionReview`** — model Eloquent con `HasFactory`; fillable: tutti i campi; metodo `casts()` Laravel 11 style; relazioni `user()` e `question()`.

- **`Database\Factories\QuestionReviewFactory`** — factory con stati `due()` (next_review_at = now()-1h) e `future()` (now()+5d).

- **`App\Services\SpacedRepetitionService`** — implementazione algoritmo SM-2 con cap 365 giorni; metodi:
  - `recordAnswer(User, int, bool): QuestionReview` — `firstOrCreate` + aggiornamento dati SR;
  - `calculateNextReview(QuestionReview, bool): array` — calcola senza persistere (testabile in isolamento);
  - `getDueQuestions(User, ?int, int): Collection` — domande in scadenza con eager load `question.category`, escluse le learned, ordinate per urgenza;
  - `getUpcomingCount(User): array` — contatori `due_today` / `due_tomorrow` / `due_this_week`;
  - `getStats(User): array` — `total_tracked` / `mastered` (rep≥5) / `learning` / `new`;
  - `getDueCountByCategory(User): array` — `[category_id => count]` per le domande in scadenza oggi.

- **`App\Http\Livewire\SmartReview`** — componente Livewire 3; properties `$reviewIds`, `$currentIndex`, `$showFeedback`, `$lastAnswerCorrect`, `$sessionStats`, `$lastIntervalDays`, `$categoryId`; `mount()` carica gli ID in scadenza; `answer(int)` registra la risposta e mostra il feedback; `nextQuestion()` avanza; `markCurrentAsLearned()` delega a `ReviewErrorsService`.

- **`resources/views/livewire/smart-review.blade.php`** — progress bar, card domanda con immagine opzionale, bottoni Vero/Falso con `wire:loading`; feedback con badge corretto/sbagliato, risposta attesa, prossima revisione in giorni; schermata di completamento con riepilogo sessione.

- **`App\Http\Controllers\Viewer\SmartReviewController`** — metodi `index()` (panoramica stats/upcoming/categorie) e `session()` (sessione ripasso, filtrabile per categoria); `abort_unless(isViewer(), 403)`.

- **`resources/views/smart-review/index.blade.php`** — 4 stat-card (tracked/mastered/learning/new), 3 info-box upcoming (oggi/domani/settimana), form filtro per categoria, empty state con CTA verso studio.

- **`resources/views/smart-review/session.blade.php`** — pagina contenitore del componente `<livewire:smart-review>` con il `categoryId` passato dalla query string.

- **Route** (`routes/web.php`, middleware `auth`):
  - `GET /smart-review` → `viewer.smart-review.index`
  - `GET /smart-review/session` → `viewer.smart-review.session`

- **Voce sidebar** `config/adminlte.php` — "Ripasso intelligente" (icon `fas fa-brain`, gate `exam-participant`, key `smart-review`) dopo "Piano di studio".

- **View Composer** `AppServiceProvider::boot()` — composer mirato su `layouts.admin` (non su `'*'`) che aggiunge il badge `danger` con il conteggio `due_today` sulla voce sidebar `smart-review`; noop se utente non viewer o count zero.

- **Widget dashboard viewer** `stats/dashboard.blade.php` — info-box `bg-gradient-primary` con conteggio `dueToday` visibile solo se > 0; link diretto alla sessione.

- **Pulsante "Ripassa" nel piano di studio** `study-plan/show.blade.php` — per ogni categoria con domande in scadenza mostra il conteggio e link filtrato alla sessione.

### Changed

- **`App\Services\QuizAttemptService`** — aggiunto `SpacedRepetitionService` nel costruttore; al termine di `record()` chiama `recordAnswer()` per ogni risposta del tentativo, aggiornando il tracking SR in modo trasparente.

- **`App\Http\Controllers\StudyController`** — nel metodo `flag()`, dopo `$this->service->recordAnswer()`, chiama `SpacedRepetitionService::recordAnswer()` per tracciare le risposte della modalità studio.

- **`App\Http\Controllers\UserStatsController`** — aggiunto `SpacedRepetitionService` nel costruttore; `me()` passa `dueToday` alla view `stats.dashboard`.

- **`App\Http\Controllers\Viewer\StudyPlanController`** — aggiunto `SpacedRepetitionService` come parametro di `show()`; passa `reviewCountByCategory` alla view del piano.

- **`tests/Feature/SpacedRepetitionTest.php`** — 12 test: 8 sull'algoritmo SM-2 puro (no DB, `calculateNextReview`), 4 di integrazione (learned exclusion, upcoming count, studio crea review, quiz attempt crea review per ogni domanda).

---

## [2026-05-25] — Feature 5.3: Test diagnostico iniziale e piano di studio suggerito

Test breve (una domanda per categoria) che il viewer può svolgere al primo accesso o on-demand dalla dashboard. Il risultato alimenta una pagina "Piano di studio" con categorie ordinate per debolezza e azioni raccomandate.

### Added

- **Migration `2026_05_25_100000_create_diagnostic_results_table`** — tabella `diagnostic_results` con colonne `id`, `user_id` (FK cascadeOnDelete), `category_id` (FK cascadeOnDelete), `correct` (boolean), `taken_at` (timestamp), `batch_id` (string 36, index); indice composito su `(user_id, category_id, taken_at)`; `down()` implementato.

- **`App\Models\DiagnosticResult`** — model senza timestamps; fillable: tutti i campi; casts `correct` → boolean, `taken_at` → datetime; relazioni `user()` e `category()`.

- **`Database\Factories\DiagnosticResultFactory`** — factory per i test.

- **`App\Services\DiagnosticService`** — metodi:
  - `generateQuestions(User): Collection` — una domanda random per ogni categoria attiva, escludendo le domande viste nelle ultime 24h dai `quiz_attempts`;
  - `saveResults(User, array): void` — persiste i risultati in `diagnostic_results` in una transazione, raggruppati da un `batch_id` UUID univoco per sessione;
  - `getLatestDiagnostic(User): ?Collection` — recupera l'ultimo batch diagnostico dell'utente;
  - `hasDiagnostic(User): bool` — helper rapido per il banner dashboard.

- **`App\Services\StudyPlanService`** — metodo `buildPlan(User): Collection` che aggrega i dati storici dai `quiz_attempts` (PHP-side, N+1 free, precarica mappa `question_id → category_id`) e incorpora l'ultimo batch diagnostico (peso 70%/30% se ci sono dati storici, 100% diagnostico altrimenti). Per ogni categoria: `mastery` (int 0–100), `attempts_count`, `recommended_action` (tre livelli: <30 / 30–70 / >70). Ritorna Collection ordinata per mastery ascendente.

- **`App\Http\Livewire\DiagnosticTest`** — componente Livewire 3; properties `$questionIds`, `$currentIndex`, `$answers`, `$completed`; `mount()` carica le domande via `DiagnosticService`; `submitAnswer(int)` avanza la domanda e salva i risultati all'ultima risposta.

- **`resources/views/livewire/diagnostic-test.blade.php`** — progress bar, card domanda con immagine opzionale, bottoni Vero/Falso con `wire:loading`; schermata di completamento con link al piano di studio.

- **`App\Http\Controllers\Viewer\StudyPlanController`** — metodi `show()` (piano di studio, 403 per non-viewer) e `startDiagnostic()` (pagina con il componente Livewire).

- **Route** (`routes/web.php`, middleware `auth`):
  - `GET /diagnostic` → `viewer.diagnostic.show`
  - `GET /study-plan` → `viewer.study-plan.show`

- **`resources/views/diagnostic/show.blade.php`** — pagina introduttiva con testo no-penalità e il componente `<livewire:diagnostic-test />`.

- **`resources/views/study-plan/show.blade.php`** — lista categorie in card: progress bar colorata (rosso/giallo/verde), badge mastery, contatore tentativi, `recommended_action`, pulsante "Studia ora" (form POST verso `study.start`); banner diagnostico in header; empty state con CTA.

- **Voce sidebar** `config/adminlte.php` — "Piano di studio" (icon `fas fa-route`, gate `exam-participant`, key `study-plan`) nel blocco STUDIO.

- **Banner dashboard viewer** `stats/dashboard.blade.php` — banner `info-box bg-gradient-info` visibile solo se `!$isAdminView && !$hasDiagnostic && total_attempts === 0`; si nasconde automaticamente dopo il primo tentativo o dopo aver fatto il diagnostico.

- **`tests/Feature/DiagnosticFeatureTest.php`** — 13 test: accesso route (auth, 403 admin), `generateQuestions` (una per categoria, no duplicati, esclude domande recenti), `saveResults` (persistenza, batch_id unico, noop su array vuoto), `hasDiagnostic`, `getLatestDiagnostic` (batch più recente).

- **`tests/Feature/StudyPlanFeatureTest.php`** — 10 test: ordinamento mastery ascendente, solo diagnostico, solo storico, senza dati (empty state, mastery 0), cascata delete user → diagnostic_results, `recommended_action` corretto.

---

## [2026-05-23] — Feature 5.2: Materiale didattico per categoria

Possibilità per admin/editor di associare materiale didattico a ogni categoria (PDF, link esterni incluso YouTube, note testuali). Il viewer visualizza i materiali nella pagina di studio della categoria, in una card collassabile prima delle domande.

### Added

- **Migration `2026_05_23_213024_create_category_materials_table`** — nuova tabella `category_materials` con colonne `id`, `category_id` (FK con `cascadeOnDelete`), `type` (enum: pdf/link/note), `title` (string 255), `url_or_path` (string 1000 nullable), `content` (text nullable), `position` (integer default 0), `created_by` (FK nullable verso users con `nullOnDelete`), timestamps; indice composito su `(category_id, position)`. `down()` implementato.

- **`App\Models\CategoryMaterial`** — model Eloquent con trait `HasFactory` e `Auditable`; fillable: `category_id`, `type`, `title`, `url_or_path`, `content`, `position`; relazioni `category()` e `creator()`; `scopeOrdered()` per ordinamento per position; accessor `embed_url` che estrae l'ID YouTube da URL `watch?v=` e `youtu.be` e restituisce l'URL embed; accessor `download_url` che restituisce `Storage::url()` per i PDF.

- **`App\Models\Category`** — aggiunta relazione `materials(): HasMany` verso `CategoryMaterial`.

- **`App\Observers\CategoryMaterialObserver`** — `creating`: imposta `created_by` dall'utente autenticato; `deleting`: elimina il file fisico PDF dallo storage (disco `public`).

- **`App\Services\CategoryMaterialService`** — metodi `create()` (salva file PDF in `materials/{category_id}/` su disco `public`, calcola `position` come max+1), `update()` (sostituisce file PDF vecchio con nuovo), `delete()` (delega eliminazione file all'observer), `reorder()` (aggiorna `position` in base all'array di ID ordinati).

- **`App\Http\Requests\StoreCategoryMaterialRequest`** e **`UpdateCategoryMaterialRequest`** — validazione di `type`, `title`, `file` (mimes:pdf, max:10240), `url_or_path` (url), `content`; autorizzazione via `canEditCategory()`.

- **`App\Http\Controllers\Admin\CategoryMaterialController`** — controller thin con metodi `index`, `create`, `store`, `edit`, `update`, `destroy`, `reorder`; autorizzazione `abort_unless(canEditCategory(), 403)`; flash messages su ogni redirect; injection di `CategoryMaterialService`.

- **Route** (`routes/web.php`, gruppo admin `middleware(['auth', '2fa'])`):
  - `Route::resource('categories.materials', ...)` per CRUD
  - `POST categories/{category}/materials/reorder` → `admin.categories.materials.reorder`

- **`resources/views/admin/categories/materials/index.blade.php`** — lista materiali con drag handle SortableJS, badge tipo colorato, autore e data; bottoni modifica/elimina; empty state con CTA; aggiornamento ordine via AJAX con feedback toastr.

- **`resources/views/admin/categories/materials/create.blade.php`** e **`edit.blade.php`** — form con campi condizionali via Alpine.js `x-show` in base al tipo selezionato (radio): input file PDF, input URL, textarea nota.

- **`resources/views/admin/categories/index.blade.php`** — aggiunto pulsante "Gestisci materiali" (`fa-book-open`) nella colonna azioni di ogni categoria.

- **`resources/views/study/play.blade.php`** — blocco "Materiale didattico" collassabile (Bootstrap collapse, default chiuso) mostrato prima della card domanda se la categoria ha almeno un materiale: PDF come link download, link YouTube come iframe responsive, link esterni con `target="_blank" rel="noopener"`, note come testo con `white-space:pre-wrap`.

- **`App\Http\Controllers\StudyController`** — `play()` ora eager-load `category` e poi `materials` (ordered) sul modello `Question` corrente, per evitare query N+1 nella view di studio.

- **`database/factories/CategoryMaterialFactory.php`** — factory con stati `pdf()`, `link()`, `youtube()` per i test.

- **`tests/Feature/CategoryMaterialTest.php`** — 13 test: creazione di ogni tipo (note, link, PDF), accesso negato a viewer, cascade delete categoria→materiali, eliminazione file fisico, visibilità materiali nella pagina studio, validazione file e URL, accessors YouTube, reorder.

---

## [2026-05-23] — Feature 5.1: Revisione errori aggregata personale

Pagina `/review-errors` per i viewer che aggrega tutte le domande sbagliate negli ultimi N tentativi completati,
con filtro per categoria, conteggio sbagli per domanda e toggle "imparata" per escludere le domande già padroneggiate.
Differisce dal bookmark (selezione manuale) e dal dettaglio tentativo (vista per-tentativo): aggrega sullo storico.

### Added

- **Migration `2026_05_23_210000_create_learned_questions_table`** — nuova tabella `learned_questions` con colonne `id`, `user_id` (FK con `cascadeOnDelete`), `question_id` (FK con `cascadeOnDelete`), `marked_at` (timestamp); indice unico composito su `(user_id, question_id)` per prevenire duplicati. `down()` implementato e reversibile.

- **`App\Models\LearnedQuestion`** — model Eloquent senza timestamps propri (`$timestamps = false`); fillable: `user_id`, `question_id`, `marked_at`; cast `marked_at => 'datetime'`; relazioni `user()` e `question()`.

- **`App\Models\User`** — aggiunta relazione `learnedQuestions(): HasMany` verso `LearnedQuestion`.

- **`App\Services\ReviewErrorsService`** — tre metodi pubblici:
  - `getErrors(User, ?int $categoryId, int $lastAttempts = 20): Collection` — carica gli ultimi N tentativi completati (filtrando `answers IS NOT NULL` e `JSON_LENGTH(answers) > 0`), itera le risposte tramite `$attempt->getAnswerResult($questionId)`, aggrega gli sbagli (result === 0) per `question_id`, esclude le domande marcate come imparate, filtra opzionalmente per categoria, ordina per `error_count desc` poi `last_wrong_at desc`. Ritorna `Collection<array{question, error_count, last_wrong_at, category}>`.
  - `markAsLearned(User, int $questionId): void` — `firstOrCreate` per idempotenza.
  - `unmarkAsLearned(User, int $questionId): void` — elimina la riga in `learned_questions`.
  - `getLearned(User, ?int $categoryId): Collection` — restituisce i `Question` marcati come imparati, opzionalmente filtrati per categoria.

- **`App\Http\Controllers\ReviewErrorsController`** — controller thin per l'area viewer:
  - `index(Request)` — autorizzazione `abort_unless(isViewer(), 403)`; valida `category_id`, `last_attempts` (between:5,50), `show_learned`; delega al service; passa `errors`, `categories`, `learnedCount` alla view.
  - `markLearned(Question)` — POST, chiama `markAsLearned`, redirect back con flash `success`.
  - `unmarkLearned(Question)` — DELETE, chiama `unmarkAsLearned`, redirect back con flash `success`.

- **Route** (`routes/web.php`, gruppo `middleware(['auth'])`):
  - `GET /review-errors` → `ReviewErrorsController@index` (`viewer.review-errors.index`)
  - `POST /review-errors/{question}/learned` → `ReviewErrorsController@markLearned` (`viewer.review-errors.learned.store`)
  - `DELETE /review-errors/{question}/learned` → `ReviewErrorsController@unmarkLearned` (`viewer.review-errors.learned.destroy`)

- **`resources/views/review-errors/index.blade.php`** — view viewer che estende `layouts.admin`:
  - Form filtro: select categoria, select `last_attempts` (10/20/30/50), toggle "Mostra solo le imparate" (auto-submit via `onchange`).
  - Card per ogni domanda: testo troncato con tooltip jQuery, badge categoria, badge "Sbagliata X volte" (grigio 1-2, giallo 3-5, rosso 6+), data ultimo sbaglio con `diffForHumans`, risposta corretta, pulsante "Studia questa categoria" che linka a `study.index?category_id=X`, pulsante toggle "Marca come imparata" / "Reinserisci negli errori" con conferma Alpine.js (`confirm()`).
  - Empty state con icona `fa-3x` e CTA contestuale (diversa se `show_learned` o no).
  - Riepilogo in fondo: conteggio errori da rivedere + domande già imparate con link.

- **Voce sidebar** (`config/adminlte.php`) — aggiunta sotto "Domande salvate" nella sezione STUDIO: `Revisione errori`, icona `fas fa-exclamation-triangle`, `can: 'exam-participant'` (solo viewer).

- **Widget dashboard viewer** (`resources/views/stats/dashboard.blade.php`) — `info-box bg-gradient-warning` visibile solo se `!$isAdminView && reviewErrorsCount > 0`; mostra il conteggio errori con link diretto a `/review-errors`.

- **`App\Http\Controllers\UserStatsController`** — iniettato `ReviewErrorsService`; nel metodo `me()` (solo per il viewer) passa `reviewErrorsCount` = `getErrors($user)->count()` alla view dashboard.

- **`tests/Feature/ReviewErrorsTest.php`** — 12 test feature con `RefreshDatabase`:
  - Accesso: unauthenticated → redirect login; admin → 403; viewer → 200.
  - Isolamento: viewer vede solo i propri errori, non quelli di altri viewer.
  - Logica errori: domanda corretta non appare; domanda sbagliata appare; tentativo con `answers = []` non conta; tentativo con `answers = null` non conta.
  - Filtri: categoria filtra correttamente; `last_attempts` limita gli attempt considerati.
  - Toggle imparata: `markAsLearned` esclude la domanda dagli errori; `unmarkAsLearned` la reinserisce; `markAsLearned` è idempotente; il toggle è personale (un viewer non influisce sugli altri).
  - Cascata: eliminazione utente rimuove le righe `learned_questions`.
  - Show learned: il toggle `show_learned=1` mostra le domande imparate.

---

## [2026-05-23] — Feature 4.3: 2FA per admin e editor

Autenticazione a due fattori (TOTP) obbligatoria per i ruoli `admin` ed `editor`.
I viewer non sono coinvolti (nessuna UI, nessun middleware sulle route viewer).
La verifica 2FA si applica a livello di gruppo di route admin tramite un unico alias middleware `'2fa'`.
Tutti i campi sensibili (`two_factor_secret`, `two_factor_recovery_codes`) sono criptati in DB via cast `encrypted`.

### Added

- **Migration `2026_05_23_084220_add_two_factor_fields_to_users_table`** — aggiunge tre colonne nullable a `users`: `two_factor_secret` (criptata via cast `encrypted`), `two_factor_enabled_at` (timestamp), `two_factor_recovery_codes` (testo criptato, deserializzato come array via cast `encrypted:array`). `down()` elimina le tre colonne senza toccare dati.

- **`App\Models\User`** — tre nuovi cast (`two_factor_secret => 'encrypted'`, `two_factor_enabled_at => 'datetime'`, `two_factor_recovery_codes => 'encrypted:array'`); tre nuovi metodi: `hasTwoFactorEnabled(): bool` (secret + data non nulli), `requiresTwoFactor(): bool` (admin o editor), `generateRecoveryCodes(int $count = 8): array` (8 token `XXXXX-XXXXX` uppercase generati con `Str::random`).

- **`App\Http\Middleware\EnsureTwoFactorAuthenticated`** — registrato come alias `'2fa'` in `bootstrap/app.php`. Logica: viewer → passa; 2FA non configurato → redirect `2fa.setup.show` con flash `warning`; `2fa_verified` assente in sessione → redirect `2fa.challenge.show`.

- **Gruppo route `/2fa/*`** (`routes/web.php`, middleware solo `auth`, fuori dal gruppo con `'2fa'` per evitare redirect loop):
  `GET /2fa/challenge` (`2fa.challenge.show`), `POST /2fa/challenge` (`2fa.challenge.verify`), `GET /2fa/setup` (`2fa.setup.show`), `POST /2fa/setup` (`2fa.setup.store`), `GET /2fa/codes` (`2fa.codes.show`), `POST /2fa/codes/confirm` (`2fa.codes.confirm`), `POST /2fa/disable` (`2fa.disable`), `POST /2fa/codes/regenerate` (`2fa.codes.regenerate`).

- **`App\Http\Controllers\Auth\TwoFactorChallengeController`** — `show()`: restituisce la view challenge o redirige se già verificato; `verify()`: verifica OTP via `Google2FA::verifyKey()`, imposta `2fa_verified = true`; `verifyRecoveryCode()`: ricerca il codice nell'array, lo rimuove (one-time use), imposta il flag.

- **`App\Http\Controllers\Auth\TwoFactorSetupController`** — `show()`: genera il secret TOTP (salvato in sessione come `2fa_setup_secret`), renderizza QR come SVG inline via `BaconQrCode\Writer` + `SvgImageBackEnd` (200 px, nessuna chiamata a API esterne); `store()`: verifica OTP contro il secret in sessione, salva i tre campi sul model, genera gli 8 recovery codes, li mette in sessione (`2fa_new_codes`); `showCodes()`: legge i codici dalla sessione (one-time display); `confirmCodes()`: svuota i codici, imposta `2fa_verified = true`; `disable()`: `validateWithBag('twoFactorDisable', ['password' => 'current_password'])`, azzera i tre campi; `regenerateCodes()`: `validateWithBag('twoFactorRegenerate', ...)`, genera nuovi codici.

- **Views 2FA** (layout `<x-guest-layout>`):
  - `resources/views/auth/two-factor-challenge.blade.php` — form OTP con campo `inputmode="numeric"` + toggle JS verso form codice di emergenza.
  - `resources/views/auth/two-factor-setup.blade.php` — SVG QR inline, secret in `<code>` per inserimento manuale, form verifica OTP.
  - `resources/views/auth/two-factor-codes.blade.php` — elenco degli 8 codici in `<code>`, avviso one-time display in `alert-warning`, pulsante di conferma.

- **Partial `resources/views/profile/partials/two-factor-form.blade.php`** — se il 2FA è attivo: data abilitazione, modal Disabilita (POST `2fa.disable` con conferma password), modal Rigenera codici (POST `2fa.codes.regenerate` con conferma password). Se non attivo: link a `2fa.setup.show`. Sezione gated su `$user->requiresTwoFactor()`.

- **`resources/views/profile/edit.blade.php`** — sezione "Autenticazione a due fattori" (`@if($user->requiresTwoFactor())`) che include il partial 2FA; toast Bootstrap per flash `success`/`warning`; JS per riaprire i modal disable/regenerate dopo redirect con errori `$errors->twoFactorDisable` / `$errors->twoFactorRegenerate`.

- **`App\Console\Commands\ResetTwoFactor`** (`2fa:reset {user_id}`) — trova l'utente per ID, verifica che il 2FA sia attivo, azzera i tre campi 2FA, logga con `Log::info()`. Utile per supporto o recovery in caso di smarrimento del dispositivo.

- **`tests/Feature/TwoFactorTest.php`** — 20 test (55 asserzioni): viewer senza sezione 2FA nel profilo, admin/editor vedono la sezione 2FA, admin senza 2FA configurato → redirect setup, editor senza 2FA → redirect setup, admin con 2FA ma senza `2fa_verified` → redirect challenge, viewer bypassa il middleware, admin con sessione verificata → accesso admin garantito, pagina setup accessibile, OTP valido abilita il 2FA e genera codici, OTP non valido non abilita, challenge OTP valido concede accesso + imposta `2fa_verified`, challenge OTP non valido nega, codice di emergenza valido concede accesso, codice consumato viene rimosso dall'array, codice già consumato fallisce al secondo uso, codice non valido nega l'accesso, disable con password corretta azzera i campi, disable con password errata non modifica nulla, logout azzera `2fa_verified`.

### Changed

- **`routes/web.php`** — gruppo route admin: da `middleware(['auth'])` a `middleware(['auth', '2fa'])`.
- **`bootstrap/app.php`** — aggiunto alias `'2fa' => \App\Http\Middleware\EnsureTwoFactorAuthenticated::class`.
- **`App\Http\Controllers\Auth\AuthenticatedSessionController`** — `destroy()` chiama `$request->session()->forget('2fa_verified')` prima di `invalidate()`.
- **`App\Console\Commands\GdprAnonymize`** — `anonymizeUserRecord()` include `two_factor_secret`, `two_factor_enabled_at`, `two_factor_recovery_codes => null` nell'UPDATE DB: i dati 2FA sono PII da eliminare insieme agli altri campi sensibili.
- **9 classi di test esistenti** — aggiunto `$this->withoutMiddleware(\App\Http\Middleware\EnsureTwoFactorAuthenticated::class)` nel `setUp()` per isolare i test non-2FA dal middleware aggiunto al gruppo admin: `AuditLogTest`, `QuestionTest`, `AdminOperativityTest`, `QuestionReportTest`, `CategoryTest`, `MitImportTest`, `NotificationsTest`, `RegistrationFlowTest`, `UserStatsTest`.

### Files

```
app/
  Console/Commands/ResetTwoFactor.php                            # nuovo: 2fa:reset {user_id}
  Console/Commands/GdprAnonymize.php                             # +3 campi 2FA nell'anonimizzazione
  Http/Controllers/Auth/TwoFactorChallengeController.php         # nuovo: show + verify + recovery
  Http/Controllers/Auth/TwoFactorSetupController.php             # nuovo: setup + codes + disable + regen
  Http/Controllers/Auth/AuthenticatedSessionController.php       # +forget('2fa_verified') al logout
  Http/Middleware/EnsureTwoFactorAuthenticated.php               # nuovo middleware
  Models/User.php                                                # +casts 2FA, +hasTwoFactorEnabled, +requiresTwoFactor, +generateRecoveryCodes
bootstrap/
  app.php                                                        # +alias '2fa'
database/migrations/
  2026_05_23_084220_add_two_factor_fields_to_users_table.php     # 3 colonne nullable + down()
resources/views/
  auth/two-factor-challenge.blade.php                            # pagina OTP + toggle recovery
  auth/two-factor-setup.blade.php                                # pagina QR SVG inline + verifica OTP
  auth/two-factor-codes.blade.php                                # pagina one-time display codici
  profile/edit.blade.php                                         # +sezione 2FA + toast + modal JS
  profile/partials/two-factor-form.blade.php                     # nuovo partial profilo
routes/
  web.php                                                        # +gruppo 2fa.*, +middleware '2fa' su admin
tests/Feature/
  TwoFactorTest.php                                              # 20 test, 55 asserzioni
  AuditLogTest.php                                               # +withoutMiddleware 2FA in setUp
  QuestionTest.php                                               # +withoutMiddleware 2FA in setUp
  AdminOperativityTest.php                                       # +withoutMiddleware 2FA in setUp
  QuestionReportTest.php                                         # +withoutMiddleware 2FA in setUp
  CategoryTest.php                                               # +withoutMiddleware 2FA in setUp
  MitImportTest.php                                              # +withoutMiddleware 2FA in setUp
  NotificationsTest.php                                          # +withoutMiddleware 2FA in setUp
  RegistrationFlowTest.php                                       # +withoutMiddleware 2FA in setUp
  UserStatsTest.php                                              # +withoutMiddleware 2FA in setUp
```

---

## [2026-05-23] — Feature 4.2: GDPR anonimizzazione utenti

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
