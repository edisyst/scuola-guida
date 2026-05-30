# ScuolaGUIDA — Quiz App

Applicazione web per la gestione di quiz della patente di guida. Gli amministratori creano domande, le raggruppano in quiz e gestiscono l'intero ciclo di vita (bozza → pubblicato → confermato); gli utenti si registrano con email/password, completano la propria scheda anagrafica e — una volta approvati dall'amministratore — richiedono l'iscrizione ai quiz ufficiali, li svolgono e consultano le proprie statistiche.

Funzionalità principali:
- **[Modalità Studio](docs/06-study-and-simulator.md#modalità-studio)** — esercitazione libera senza timer né punteggio, con materiale didattico per categoria (PDF, video YouTube, note).
- **[Simulatore Esame](docs/06-study-and-simulator.md#simulatore-esame)** — riproduce il formato ufficiale ministeriale (30 domande, 20 minuti, max 3 errori).
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

**Stack:** Laravel 11 · Blade · AdminLTE 3 · Bootstrap 5 · Livewire 3 · Alpine.js · MySQL · Redis

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

## Documentazione

| File | Contenuto |
|---|---|
| [docs/01-installation.md](docs/01-installation.md) | Installazione completa, env vars, comandi artisan, troubleshooting |
| [docs/02-architecture.md](docs/02-architecture.md) | Flusso request, Livewire 3, ruoli, cicli di vita (con diagrammi SVG) |
| [docs/03-features.md](docs/03-features.md) | Catalogo funzionalità admin/editor e viewer, badge sidebar, dashboard utente |
| [docs/04-notifications.md](docs/04-notifications.md) | Sistema notifiche email + in-app, bell Livewire, payload contract |
| [docs/05-security.md](docs/05-security.md) | Ruoli & permessi, 2FA, GDPR anonimizzazione |
| [docs/06-study-and-simulator.md](docs/06-study-and-simulator.md) | Modalità studio, simulatore esame, struttura `QuizAttempt.answers` |
| [docs/07-pwa.md](docs/07-pwa.md) | PWA: cosa funziona offline, installazione, versionamento service worker |
| [docs/08-ui-patterns.md](docs/08-ui-patterns.md) | Convenzioni UI/Livewire per chi sviluppa (design system `sg-*`) |
| [docs/09-testing.md](docs/09-testing.md) | Copertura test (~290 test in ~24 classi Feature) e pattern ricorrenti |
| [CHANGELOG.md](CHANGELOG.md) | Storico modifiche per feature/release (Keep a Changelog) |
| [CLAUDE.md](CLAUDE.md) | Convenzioni operative e architetturali del progetto |

---

## Test

Suite con ~380 Feature test in ~25 classi (Laravel TestCase + `RefreshDatabase`):

```bash
php artisan test
```

Per la mappa completa dei file di test e i pattern ricorrenti (Livewire, fake notifications, file upload, bypass middleware 2FA) vedi **[docs/09-testing.md](docs/09-testing.md)**.

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
