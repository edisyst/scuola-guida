# ScuolaGUIDA — Quiz App

Applicazione web per la gestione di quiz della patente di guida. Gli amministratori creano domande, le raggruppano in quiz e gestiscono l'intero ciclo di vita (bozza → pubblicato → confermato); gli utenti si registrano con email/password, completano la propria scheda anagrafica e — una volta approvati dall'amministratore — richiedono l'iscrizione ai quiz ufficiali, li svolgono e consultano le proprie statistiche.

Funzionalità principali:
- **[Modalità Studio](docs/06-study-and-simulator.md#modalità-studio)** — esercitazione libera senza timer né punteggio, con materiale didattico per categoria (PDF, video YouTube, note). Filtrata per il tipo di patente in studio scelto dal viewer.
- **[Simulatore Esame](docs/06-study-and-simulator.md#simulatore-esame)** — riproduce il formato ufficiale ministeriale (30 domande, 20 minuti, max 3 errori), personalizzato per il tipo di patente. Domande filtrate per le categorie della patente scelta.
- **[Multi-patente](docs/04-license-types.md)** — supporto completo di tutti i tipi di patente italiano (AM, A1, A2, A, B, B96, BE, C1, C1E, C, CE, D1, D1E, D, DE, CQC); ogni tipo ha categoria associate e formato esame configurabile. Ogni viewer sceglie il tipo per cui sta studiando (profilo → "Patente in studio"); studio, simulatore, diagnostico e ripasso SM-2 vengono filtrati di conseguenza.
- **[Domande salvate](docs/03-features.md#area-utente-viewer)** — bookmark persistente con nota personale opzionale.
- **[Segnalazione errori](docs/03-features.md#area-utente-viewer)** — il viewer può segnalare problemi sulle domande; l'admin modera.
- **[Revisione errori](docs/03-features.md#area-utente-viewer)** — aggregato delle domande sbagliate con toggle "imparata".
- **[Test diagnostico + Piano di studio](docs/03-features.md#area-utente-viewer)** — una domanda per categoria; le categorie vengono ordinate per debolezza con azioni di studio consigliate.
- **[Ripasso intelligente](docs/03-features.md#area-utente-viewer)** — algoritmo SM-2 che traccia ogni risposta e propone sessioni di ripasso ordinate per urgenza.
- **[Gamification](docs/03-features.md#area-utente-viewer)** — streak giorni consecutivi e badge per milestone, con notifica in-app al guadagno.
- **[PWA installabile](docs/07-pwa.md)** — la modalità studio funziona anche offline.
- **[2FA obbligatoria](docs/05-security.md#autenticazione-a-due-fattori-2fa)** per admin/editor (TOTP) con codici di emergenza.
- **Report periodici** (admin) — aggregati mensili/trimestrali su tutti i quiz confermati: tentativi, studenti attivi, tasso di promozione, punteggio medio, distribuzione per categoria, top domande più sbagliate. Export PDF e confronto con il periodo precedente.
- **Versionamento domande** — ogni modifica ai campi versionabili crea uno snapshot immutabile; la revisione storica di un tentativo mostra sempre il testo e la risposta che il viewer ha effettivamente visto, anche dopo modifiche successive alla domanda.
- **[Backup automatico + Health dashboard](docs/10-backup-health.md)** — backup giornaliero di DB e media tramite `spatie/laravel-backup`, retention configurabile, notifica agli admin in caso di fallimento; dashboard admin con stato backup, code, spazio disco e ultimi errori di log.
- **Audit log con filtri e diff** — ogni modifica al sistema è tracciata e consultabile da admin con filtri per utente, modello, tipo azione e range date; pannello diff Prima/Dopo per ogni voce; export Excel con i filtri attivi. Gestione corretta degli utenti anonimizzati (GDPR).
- **Area istruttore evoluta** — il ruolo `instructor` può aggiungere note testuali sui propri studenti assegnati, riceve una notifica automatica (mail + in-app + push) al completamento di ogni quiz e può esportare un PDF riassuntivo dei progressi (KPI, tentativi, badge, note) da condividere con la scuola guida. I permessi di edit sui contenuti restano invariati. Gli admin assegnano gli studenti tramite il pannello `Gestione istruttori` e possono esportare il PDF per qualsiasi studente.
- **[Web Push Notifications](docs/07-pwa.md#web-push-notifications-feature-67)** — quarto canale di notifica nativo (browser chiuso / dispositivo bloccato). Il viewer si iscrive dal profilo; le push affiancano mail e database per approvazione iscrizione, badge guadagnati e promemoria ripasso SM-2 (schedulato alle 08:00).
- **GDPR portabilità dati (art. 20)** — il viewer scarica un archivio ZIP con tutti i propri dati personali in formato JSON (quiz, bookmark, badge, attività, SM-2, documento d'identità). L'admin/editor può esportare i dati di qualsiasi utente da `/admin/users/{id}/edit`. Ogni export è tracciato nell'audit log; il file ZIP viene eliminato subito dopo l'invio (`deleteFileAfterSend`). Cleanup notturno automatico alle 03:00 via `gdpr:export --cleanup-only`.

- **Interfaccia multilingua (IT/EN/ES)** — menu, navbar, **tutte le pagine del viewer** e **tutta l'area backend** (admin, editor, instructor) sono disponibili in italiano, inglese e spagnolo. Il cambio lingua avviene tramite un dropdown con bandierine nella navbar; la scelta è persistita in sessione. I dati applicativi (quiz, domande, categorie) restano in italiano. Aggiungere una nuova lingua richiede creare i file in `lang/{code}/` e aggiungere l'entry in `config/locales.php`. Le tabelle DataTables si localizzano automaticamente via `meta[name="datatables-i18n"]` + `public/js/datatables-i18n.js`, senza inline scripts.
- **Accessibilità DSA — lettura audio TTS** — ogni viewer può attivare la lettura audio delle domande tramite la Web Speech API (zero costo server, funziona offline). Il toggle e l'opzione di avvio automatico sono configurabili dal profilo. Il supporto replica l'ausilio per candidati con DSA previsto dal D.Lgs. 62/2017 e dalle disposizioni MIT sull'esame teorico.

**Stack:** Laravel 11 · Blade · AdminLTE 3 · Bootstrap 5 · Livewire 3 · Alpine.js · MySQL · Redis · `laravel-notification-channels/webpush`

## Panoramica architettura

![](docs/diagrams/mind-map-scuola-guida.svg)

---

## Quick start

```bash
git clone <url-repo> scuola-guida
cd scuola-guida
composer install
npm install
cp .env.example .env
php artisan key:generate
# imposta DB_* in .env; avvia Redis (Laragon: tray → Redis → Start), poi:
php artisan migrate:fresh --seed
php artisan storage:link
npm run dev          # terminale 1
php artisan serve    # terminale 2
```

Login admin di sviluppo: `admin@test.com` / `password` → [http://127.0.0.1:8000/admin/quizzes](http://127.0.0.1:8000/admin/quizzes).

Per il setup completo vedi:
- [Prerequisiti e clone](docs/01-installation.md#prerequisiti)
- [Database e dati iniziali](docs/01-installation.md#4-database-e-dati-iniziali) (incluso il file Excel richiesto per il seeding reale)
- [Email Mailtrap](docs/01-installation.md#6-email-di-notifica-mailtrap)
- [Worker della coda email](docs/01-installation.md#7-worker-della-coda-email)
- [Scheduler](docs/01-installation.md#8-scheduler-chiusura-automatica-iscrizioni-scadute)
- [Comandi artisan utili](docs/01-installation.md#comandi-artisan-utili)
- [Variabili `.env` rilevanti](docs/01-installation.md#variabili-env-rilevanti)
- [Risoluzione problemi comuni](docs/01-installation.md#risoluzione-problemi-comuni)

---

## Docker (CI / onboarding)

Il `docker-compose.yml` nella root avvia lo stack completo (app, nginx, MySQL 8, Redis 7)
per l'ambiente di CI e per l'onboarding su macchine senza Laragon. **Non** sostituisce
Laragon sullo sviluppo Windows: rimane la scelta consigliata per lo sviluppo locale.

```bash
cp .env.docker.example .env
docker compose up -d
docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate --seed
docker compose exec app php artisan storage:link
```

L'app è raggiungibile su `http://localhost`. Per fermare lo stack: `docker compose down`.

---

## Localizzazione

L'interfaccia supporta **italiano** (default), **inglese** e **spagnolo**. Sono tradotte:
- Menu/navbar (sidebar, dropdown bandierine)
- Tutte le pagine del viewer: dashboard, gamification/badge, profilo/iscrizione anagrafica, iscrizioni quiz, notifiche, revisione errori, ripasso SM-2, modalità studio, simulatore, risultati
- Flash messages dei flussi viewer ed editor/admin
- Email e notifiche in-app/webpush inviate ai viewer
- **Tutta l'area backend** (admin, editor, instructor): titoli pagina, header, filtri, colonne tabelle, action label, confirm dialog, toast di successo/errore
- **Tabelle DataTables**: stringhe UI (search, paginazione, contatori) localizzate automaticamente via `public/js/datatables-i18n.js`
- **Notifiche sistema** (`BackupFailed`) renderizzate nella lingua dell'utente destinatario

I dati applicativi (testo domande, categorie) restano in italiano — la traduzione del testo delle domande è un sistema separato (Feature 7.1).

### Preferenza lingua e riconciliazione

- **Utenti autenticati**: la lingua dell'interfaccia è letta da `users.locale` (fonte primaria). Quando l'utente cambia lingua dal dropdown, la scelta è persisted su `users.locale` e in sessione.
- **Ospiti**: la lingua viene letta dalla sessione/cookie.
- Notifiche e Mailable in coda vengono renderizzati nel locale dell'utente automaticamente (`User` implementa `HasLocalePreference`).
- Il Middleware `SetLocale` è l'unico punto che chiama `App::setLocale()`.

### File di traduzione

Ogni locale ha i seguenti file in `lang/{locale}/`:

| File | Contenuto |
|---|---|
| `menu.php` | Sidebar, navbar, dropdown lingua |
| `viewer.php` | Studio, simulatore, quiz (161 chiavi) |
| `common.php` | Bottoni condivisi, azioni, unità plurali |
| `nav.php` | Notification bell e pagina notifiche |
| `dashboard.php` | KPI, widget, grafici dashboard viewer |
| `gamification.php` | Badge, streak, progressione |
| `profile.php` | Form iscrizione anagrafica, campi, stati, TTS, 2FA, password, elimina account, badge stato |
| `enrollments.php` | Pagina "Le mie iscrizioni" (viewer) e gestione iscrizioni admin |
| `flags.php` | Segnalazione errori lato viewer (bottone + form) |
| `review.php` | Revisione errori, ripasso SM-2 (indice + sessione), diagnostico, piano di studio, domande salvate |
| `flash.php` | Flash messages viewer **e backend** (CRUD domande, quiz, categorie, utenti, media, backup, instructor note, …) |
| `notifications.php` | Oggetti e corpi notifiche/email viewer e sistema (`BackupFailed`, `NewReport`, outcome istruttore) |
| `questions.php` | Pagine admin/editor domande: indice, filtri, colonne, azioni, versionamento |
| `quiz.php` | Pagine admin/editor quiz: indice, stati, azioni, confirm, scheduling |
| `categories.php` | Pagine admin categorie: indice, materiali, colonne, confirm |
| `users.php` | Pagina admin utenti: colonne, permessi, azioni, confirm |
| `reports.php` | Pagina admin report: form, preset, azioni generate/export |
| `audit.php` | Log audit: filtri, tipi evento, colonne, diff panel, export |
| `media.php` | Media manager: upload, rename, delete (modal con warning refs) |
| `backup.php` | Health dashboard: stato backup, code, DB, disco, log errori |
| `instructor.php` | Area instructor: overview, dettaglio studente, KPI, note, badge, tentativi |
| `editor.php` | Dashboard editor: filtri, KPI, grafici, sezioni report |
| `nav_admin.php` | Titoli pagina, breadcrumb e voci sidebar area backend |
| `datatables.php` | Stringhe UI DataTables (search, paginate, info, zero records) |
| `auth.php` | Messaggi di autenticazione |
| `validation.php` | Messaggi di validazione form |
| `passwords.php` | Reset password |
| `pagination.php` | Paginazione |

### Aggiungere una nuova lingua

1. Creare tutti i file `lang/{code}/*.php` (copiare da `lang/it/` e tradurre).
2. Aggiungere un'entry in `config/locales.php` (array `supported`) con `label` e `flag`.
3. Salvare il file SVG della bandiera in `public/images/language_flags/{code}.svg`.

Nessuna modifica al codice applicativo è richiesta. Il fallback è sempre l'italiano. I test di copertura i18n sono in `tests/Feature/LocalizationTest.php` (area viewer) e `tests/Feature/LocalizationBackendTest.php` (area admin/editor/instructor).

> **Nota**: la funzionalità non è compatibile con `php artisan config:cache` perché i
> testi del menu sono tradotti a runtime dal `LangFilter` di AdminLTE, che legge i file
> `lang/{locale}/menu.php` ad ogni request. Non eseguire config:cache in produzione se
> si usa il cambio lingua dinamico.

### Accessibilità esame — traduzione del testo delle domande (Feature 7.1)

Concetto **distinto** dalla i18n dell'interfaccia: qui si traduce il **testo delle domande**
(non la UI) per l'accessibilità dell'esame teorico MIT. Admin ed editor caricano le traduzioni
dalla pagina di modifica domanda (`/admin/questions/{id}/edit`, sezione "Traduzioni"); il viewer sceglie la lingua preferita nella
card "Lingua preferita" del proprio profilo. La traduzione si applica in modalità studio, nel
simulatore e nel test diagnostico, con **fallback automatico all'italiano** se manca.

Le lingue d'esame disponibili sono configurate in `config/locales.php` sotto la chiave `exam`
(`it`, `en`, `fr`, `de`, `es`). Aggiungere una lingua = una entry lì, nessuna modifica al codice.
Il testo italiano resta la fonte di verità: le traduzioni sono entità separate e non rientrano
nel versionamento domande (Feature 6.2).

---

## Documentazione

| File | Contenuto |
|---|---|
| [docs/01-installation.md](docs/01-installation.md) | Installazione completa, env vars, comandi artisan, troubleshooting |
| [docs/02-architecture.md](docs/02-architecture.md) | Flusso request, Livewire 3, ruoli, cicli di vita (con diagrammi SVG) |
| [docs/03-features.md](docs/03-features.md) | Catalogo funzionalità admin/editor e viewer, badge sidebar, dashboard utente |
| [docs/04-notifications.md](docs/04-notifications.md) | Sistema notifiche email + in-app, bell Livewire, payload contract |
| [docs/05-security.md](docs/05-security.md) | Ruoli & permessi, 2FA, GDPR anonimizzazione (art. 17) e portabilità (art. 20) |
| [docs/06-study-and-simulator.md](docs/06-study-and-simulator.md) | Modalità studio, simulatore esame, struttura `QuizAttempt.answers` |
| [docs/07-pwa.md](docs/07-pwa.md) | PWA: cosa funziona offline, installazione, versionamento service worker |
| [docs/08-ui-patterns.md](docs/08-ui-patterns.md) | Convenzioni UI/Livewire per chi sviluppa (design system `sg-*`) |
| [docs/09-testing.md](docs/09-testing.md) | Copertura test (~380 test in ~34 classi Feature) e pattern ricorrenti |
| [docs/10-backup-health.md](docs/10-backup-health.md) | Backup automatico, scheduler, cron produzione, Health dashboard, ripristino |
| [CHANGELOG.md](CHANGELOG.md) | Storico modifiche per feature/release (Keep a Changelog) |
| [CLAUDE.md](CLAUDE.md) | Convenzioni operative e architetturali del progetto |

---

## Test

Suite con ~412 Feature test in ~36 classi (Laravel TestCase + `RefreshDatabase`):

```bash
php artisan test
```

Per la mappa completa dei file di test e i pattern ricorrenti (Livewire, fake notifications, file upload, bypass middleware 2FA) vedi **[docs/09-testing.md](docs/09-testing.md)**.

### Analisi statica

```bash
composer analyse    # PHPStan livello 5 via Larastan — deve restare a 0 errori nuovi
composer lint       # Laravel Pint in modalità --test (solo verifica, no modifica)
```

La baseline `phpstan-baseline.neon` contiene i 140 errori pre-esistenti; ogni commit
nuovo deve restare a **0 errori fuori baseline**. Per aggiornare la baseline dopo un
refactor legittimo: `./vendor/bin/phpstan analyse --generate-baseline`.

### Test browser E2E (Laravel Dusk)

Tre test browser automatizzati per i flussi critici (login 2FA, simulatore completo,
flusso iscrizione quiz). Richiedono Chrome installato e l'app in esecuzione.

```bash
php artisan dusk            # esegue tutti i test browser
php artisan dusk --filter LoginWith2faTest
```

Per lo sviluppo locale, Dusk usa `.env.dusk.local` (override di `.env`): assicurarsi
che `APP_URL` punti all'istanza attiva (`http://127.0.0.1:8000` con Laragon).

---

## Dipendenze principali

| Package | Uso | Documentazione |
|---|---|---|
| `jeroennoten/laravel-adminlte` | Template admin con sidebar, navbar, widget | [ui-patterns](docs/08-ui-patterns.md) |
| `livewire/livewire` | Componenti dinamici (NotificationBell, BookmarkButton, ReportButton, MediaManager, SmartReview, DiagnosticTest) | [ui-patterns](docs/08-ui-patterns.md), [architecture](docs/02-architecture.md#2-livewire-3-components) |
| `maatwebsite/excel` | Import/export domande via Excel; export risultati quiz | [installation](docs/01-installation.md#comandi-artisan-utili) |
| `yajra/laravel-datatables` | Tabelle con ricerca/ordinamento server-side | — |
| `pragmarx/google2fa-laravel` | Autenticazione TOTP (2FA) per admin ed editor | [security](docs/05-security.md#autenticazione-a-due-fattori-2fa) |
| `bacon/bacon-qr-code` | Generazione QR code SVG inline per la pagina di setup 2FA | [security](docs/05-security.md#flusso-di-configurazione-primo-accesso) |
| `predis/predis` | Client Redis PHP puro — cache driver (nessuna estensione C richiesta) | — |
| `laravel/breeze` | Scaffolding autenticazione (Blade preset, dev) | — |
| `alpinejs` | Interattività JS leggera (toggle, dropdown, feedback studio) | [ui-patterns](docs/08-ui-patterns.md) |
| `barryvdh/laravel-debugbar` | Debug toolbar (solo sviluppo) | — |
| `laravel/pint` | Code style (solo sviluppo) | — |
| `larastan/larastan` | Analisi statica PHPStan livello 5 per Laravel (solo sviluppo) | — |
| `laravel/dusk` | Test browser E2E con ChromeDriver (solo sviluppo) | — |
| `spatie/laravel-backup` | Backup automatico DB + media, retention policy, notifica fallimento | [backup-health](docs/10-backup-health.md) |
