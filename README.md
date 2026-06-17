# ScuolaGUIDA — Quiz App

Applicazione web per la gestione di quiz della patente di guida. Gli amministratori creano domande, le raggruppano in quiz e gestiscono l'intero ciclo di vita (bozza → pubblicato → confermato); gli utenti si registrano con email/password, completano la propria scheda anagrafica e — una volta approvati dall'amministratore — richiedono l'iscrizione ai quiz ufficiali, li svolgono e consultano le proprie statistiche.

Funzionalità principali:
- **[Modalità Studio](docs/06-study-and-simulator.md#modalità-studio)** — esercitazione libera senza timer né punteggio, con materiale didattico per categoria (PDF, video YouTube, note). Filtrata per il tipo di patente in studio scelto dal viewer.
- **[Simulatore Esame](docs/06-study-and-simulator.md#simulatore-esame)** — riproduce il formato ufficiale ministeriale (30 domande, 20 minuti, max 3 errori), personalizzato per il tipo di patente. Domande filtrate per le categorie della patente scelta.
- **[Multi-patente](docs/03-features.md#area-admin--editor)** — supporto completo di tutti i tipi di patente italiano (AM, A1, A2, A, B, B96, BE, C1, C1E, C, CE, D1, D1E, D, DE, CQC); ogni tipo ha categoria associate e formato esame configurabile. Ogni viewer sceglie il tipo per cui sta studiando (profilo → "Patente in studio"); studio, simulatore, diagnostico e ripasso SM-2 vengono filtrati di conseguenza.
- **[Domande salvate](docs/03-features.md#area-utente-viewer)** — bookmark persistente con nota personale opzionale.
- **[Segnalazione errori](docs/03-features.md#area-utente-viewer)** — il viewer può segnalare problemi sulle domande; l'admin modera.
- **[Revisione errori](docs/03-features.md#area-utente-viewer)** — aggregato delle domande sbagliate con toggle "imparata".
- **[Test diagnostico + Piano di studio](docs/03-features.md#area-utente-viewer)** — una domanda per categoria; le categorie vengono ordinate per debolezza con azioni di studio consigliate.
- **[Ripasso intelligente](docs/03-features.md#area-utente-viewer)** — algoritmo SM-2 che traccia ogni risposta e propone sessioni di ripasso ordinate per urgenza.
- **[Gamification](docs/03-features.md#area-utente-viewer)** — streak giorni consecutivi e badge per milestone, con notifica in-app al guadagno.
- **[PWA installabile](docs/07-pwa.md)** — la modalità studio funziona anche offline.
- **[2FA obbligatoria](docs/05-security.md#autenticazione-a-due-fattori-2fa)** per admin/editor (TOTP) con codici di emergenza.
- **Report periodici** (admin) — aggregati mensili/trimestrali su tutti i quiz confermati: tentativi, studenti attivi, tasso di promozione, punteggio medio, distribuzione per categoria, top domande più sbagliate. Export PDF e confronto con il periodo precedente. Segmentazione opzionale per tipo di patente; dashboard editor con KPI filtrabili per tipo. Comando `reports:generate-by-license` per generazione batch automatica.
- **Versionamento domande** — ogni modifica ai campi versionabili crea uno snapshot immutabile; la revisione storica di un tentativo mostra sempre il testo e la risposta che il viewer ha effettivamente visto, anche dopo modifiche successive alla domanda.
- **Homepage guest personalizzabile (Feature 11.1)** — landing page per visitatori non autenticati con navbar, hero con carosello immagini come sfondo (max 4 slide, 1920×600 px, gestibili da `admin/system/settings`), sezione statistiche, feature highlights, badge tipi di patente e CTA finale. Tutti i testi della scuola (nome, tagline, logo) vengono da `setting()`. Hero e carosello in riquadro centrato all'80%, palette chiara (`#f4f6f9` / `#eef2ff`), sempre light mode. Fallback sfondo solid `--sg-accent` se nessuna immagine. Layout `layouts/guest.blade.php` separato da AdminLTE (Bootstrap 5.3 + Alpine via CDN), i18n IT/EN/ES. Richiede `php artisan db:seed --class=SystemSettingSeeder` al primo setup.
- **Pannello sistema — stato servizi e personalizzazione (Feature 11.0)** — pannello admin con due sezioni: *Stato servizi* (6 indicatori live: Database, Redis, Queue, Storage, Mail, Twilio) e *Personalizzazione* (nome scuola, tagline, indirizzo, contatti, n. autorizzazione MIT, logo chiaro/dark, colore accent). I dati della scuola sono la fonte di verità per tutte le view tramite l'helper `setting('school.name')`. Il logo viene servito via `Storage::url()` dalla navbar. Il colore accent è iniettato come variabile CSS `--sg-accent`.
- **[Backup automatico + Health dashboard](docs/10-backup-health.md)** — backup giornaliero di DB e media tramite `spatie/laravel-backup`, retention configurabile, notifica agli admin in caso di fallimento; dashboard admin con stato backup, code, spazio disco e ultimi errori di log.
- **Audit log con filtri e diff** — ogni modifica al sistema è tracciata e consultabile da admin con filtri per utente, modello, tipo azione e range date; pannello diff Prima/Dopo per ogni voce; export Excel con i filtri attivi. Gestione corretta degli utenti anonimizzati (GDPR).
- **Area istruttore evoluta** — il ruolo `instructor` può aggiungere note testuali sui propri studenti assegnati, riceve una notifica automatica (mail + in-app + push) al completamento di ogni quiz e può esportare un PDF riassuntivo dei progressi (KPI, tentativi, badge, note) da condividere con la scuola guida. I permessi di edit sui contenuti restano invariati. Gli admin assegnano gli studenti tramite il pannello `Gestione istruttori` e possono esportare il PDF per qualsiasi studente.
- **[Web Push Notifications](docs/07-pwa.md#web-push-notifications-feature-67)** — quarto canale di notifica nativo (browser chiuso / dispositivo bloccato). Il viewer si iscrive dal profilo; le push affiancano mail e database per approvazione iscrizione, badge guadagnati e promemoria ripasso SM-2 (schedulato alle 08:00).
- **GDPR portabilità dati (art. 20)** — il viewer scarica un archivio ZIP con tutti i propri dati personali in formato JSON (quiz, bookmark, badge, attività, SM-2, documento d'identità). L'admin/editor può esportare i dati di qualsiasi utente da `/admin/users/{id}/edit`. Ogni export è tracciato nell'audit log; il file ZIP viene eliminato subito dopo l'invio (`deleteFileAfterSend`). Cleanup notturno automatico alle 03:00 via `gdpr:export --cleanup-only`.

- **Interfaccia multilingua (IT/EN/ES)** — menu, navbar, **tutte le pagine del viewer** e **tutta l'area backend** (admin, editor, instructor) sono disponibili in italiano, inglese e spagnolo. Il cambio lingua avviene tramite un dropdown con bandierine nella navbar; la scelta è persistita in sessione. I dati applicativi (quiz, domande, categorie) restano in italiano. Aggiungere una nuova lingua richiede creare i file in `lang/{code}/` e aggiungere l'entry in `config/locales.php`. Le tabelle DataTables si localizzano automaticamente via `meta[name="datatables-i18n"]` + `public/js/datatables-i18n.js`, senza inline scripts.
- **Accessibilità DSA — lettura audio TTS** — ogni viewer può attivare la lettura audio delle domande tramite la Web Speech API (zero costo server, funziona offline). Il toggle e l'opzione di avvio automatico sono configurabili dal profilo. Il supporto replica l'ausilio per candidati con DSA previsto dal D.Lgs. 62/2017 e dalle disposizioni MIT sull'esame teorico.
- **Guide pratiche — moduli, sessioni e sequenzialità (D.M. MIT 294/2025)** — l'admin configura i moduli di guida pratica per tipo di patente (codice, ore richieste, ordine MIT); istruttori e admin registrano le sessioni dei singoli studenti; ogni viewer consulta il proprio avanzamento. Il percorso è **obbligatoriamente sequenziale**: il modulo A è propedeutico a tutti gli altri (A → B → C → D); tentare di registrare un modulo con precedenti incompleti ritorna HTTP 422. Il controllo avviene a doppio livello (controller + service). Al completamento di tutte le ore obbligatorie si sblocca la **certificazione finale**, visibile con data su dashboard viewer, area istruttore e PDF. Il calcolo avanzamento e lo stato certificazione avvengono in due query senza N+1.
- **Export PDF attestazione guide pratiche** — il viewer completo scarica un riepilogo PDF delle proprie sessioni quando ha terminato tutte le ore obbligatorie; l'istruttore e l'admin possono esportare il PDF per qualsiasi studente. Il documento contiene intestazione autoscuola (configurabile tramite variabili `.env` `DRIVING_SCHOOL_*`), dati studente, riepilogo avanzamento per modulo, **stato certificazione con data di completamento**, dettaglio sessioni, elenco istruttori. Il file è generato on-demand e rimosso al download. Cleanup automatico alle 03:30.

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

## Configurazione e personalizzazione

Questa sezione raccoglie tutti i punti in cui il comportamento dell'applicazione è
modificabile senza toccare il codice: variabili d'ambiente, file `config/`, pannello
di sistema e comandi artisan eseguibili dall'interfaccia admin.

---

### Variabili `.env`

#### Identità applicazione

| Variabile | Default | Descrizione |
|---|---|---|
| `APP_NAME` | `Scuola Guida` | Nome mostrato in navbar, mail e tab del browser |
| `APP_ENV` | `local` | Ambiente (`local` / `production`) |
| `APP_DEBUG` | `true` | Modalità debug (false in produzione) |
| `APP_URL` | `http://localhost` | URL base usato nei link delle notifiche mail |
| `APP_LOCALE` | `it` | Lingua di default dell'interfaccia (`it` / `en` / `es`) |
| `APP_TIMEZONE` | `UTC` | Fuso orario applicazione |

#### Autenticazione e sicurezza

| Variabile | Default | Descrizione |
|---|---|---|
| `TWO_FACTOR_ENABLED` | `true` | Obbligo 2FA per admin/editor. `false` = disabilita middleware e nasconde sezione profilo |

#### Cache e performance

| Variabile | Default | Descrizione |
|---|---|---|
| `CACHE_ENABLED` | `true` | Master switch cache. `false` = driver `null` (tutto live, utile per debug) |
| `CACHE_STORE` | `redis` | Backend cache (`redis` / `file` / `database` / `memcached`) |
| `REDIS_CLIENT` | `predis` | Client Redis (`predis` = puro PHP, `phpredis` = estensione C) |
| `REDIS_HOST` | `127.0.0.1` | Host Redis |
| `REDIS_PORT` | `6379` | Porta Redis |
| `REDIS_CACHE_DB` | `1` | Database Redis per la cache |

#### Code e sessioni

| Variabile | Default | Descrizione |
|---|---|---|
| `QUEUE_CONNECTION` | `database` | Backend code (`database` / `redis` / `beanstalkd`) |
| `SESSION_DRIVER` | `database` | Backend sessioni (`database` / `redis`) |
| `SESSION_LIFETIME` | `120` | Durata sessione in minuti |

#### Email

| Variabile | Default | Descrizione |
|---|---|---|
| `MAIL_MAILER` | `smtp` | Driver mail (`smtp` / `log` / `array`) |
| `MAIL_HOST` | — | Host SMTP |
| `MAIL_PORT` | — | Porta SMTP |
| `MAIL_USERNAME` | — | Username SMTP |
| `MAIL_PASSWORD` | — | Password SMTP |
| `MAIL_ENCRYPTION` | `tls` | Cifratura (`tls` / `ssl`) |
| `MAIL_FROM_ADDRESS` | — | Mittente delle notifiche di sistema |
| `MAIL_FROM_NAME` | `${APP_NAME}` | Nome mittente |

#### Twilio (health check)

Le variabili Twilio non abilitano invio SMS nell'app — sono usate solo da `SystemHealthService`
per mostrare lo stato dell'integrazione nel pannello `/admin/system/health`.

| Variabile | Default | Descrizione |
|---|---|---|
| `MESSAGING_ENABLED` | `false` | Se `false`, il pannello salute mostra warning Twilio non configurato |
| `TWILIO_ACCOUNT_SID` | — | Account SID Twilio (health check) |
| `TWILIO_AUTH_TOKEN` | — | Auth Token Twilio (health check) |

#### Web Push Notifications (VAPID)

| Variabile | Default | Descrizione |
|---|---|---|
| `VAPID_SUBJECT` | `mailto:admin@scuolaguida.local` | Subject VAPID (URL `https://` o email) |
| `VAPID_PUBLIC_KEY` | — | Chiave pubblica VAPID (87 caratteri base64url) |
| `VAPID_PRIVATE_KEY` | — | Chiave privata VAPID (43 caratteri base64url) |

Generazione chiavi: `node -e "const w=require('web-push'); const k=w.generateVAPIDKeys(); console.log(k)"`

#### Backup

| Variabile | Default | Descrizione |
|---|---|---|
| `BACKUP_DISK` | `backups` | Disco di destinazione dei backup (definito in `config/filesystems.php`) |
| `BACKUP_ARCHIVE_PASSWORD` | — | Password ZIP del backup (vuoto = senza password) |
| `BACKUP_NOTIFY_ON_SUCCESS` | `false` | Invia email di conferma anche sui backup riusciti |
| `BACKUP_NOTIFICATION_EMAIL` | `MAIL_FROM_ADDRESS` | Email per notifiche di fallimento backup |
| `BACKUP_KEEP_ALL_DAYS` | `7` | Giorni in cui conservare tutti i backup |
| `BACKUP_KEEP_DAILY` | `16` | Giorni in cui conservare i backup giornalieri |
| `BACKUP_KEEP_WEEKLY` | `8` | Settimane in cui conservare i backup settimanali |
| `BACKUP_KEEP_MONTHLY` | `4` | Mesi in cui conservare i backup mensili |

#### Media e storage

| Variabile | Default | Descrizione |
|---|---|---|
| `FILESYSTEM_DISK` | `local` | Disco default (`local` / `s3`) |
| `MEDIA_DISK` | `public` | Disco per le immagini delle domande |
| `MEDIA_ACTIVE_DIR` | `test` | Cartella attiva (`test` / `production`) |

#### Dati autoscuola (attestazioni guide pratiche)

Questi valori alimentano l'intestazione del PDF di attestazione guide pratiche.
Dal Feature 11.0 la fonte primaria è la tabella `system_settings` (editabile da admin);
questi env rimangono come **fallback** se il record DB non esiste.

| Variabile | Descrizione |
|---|---|
| `DRIVING_SCHOOL_NAME` | Ragione sociale dell'autoscuola |
| `DRIVING_SCHOOL_ADDRESS` | Indirizzo sede |
| `DRIVING_SCHOOL_PHONE` | Telefono |
| `DRIVING_SCHOOL_EMAIL` | Email pubblica |
| `DRIVING_SCHOOL_LICENSE` | Numero autorizzazione MIT |

---

### File `config/` — opzioni per feature

#### `config/simulator.php` — Regole esame simulato

| Chiave | Default | Descrizione |
|---|---|---|
| `questions` | `30` | Numero domande per esame |
| `time_limit` | `20` | Durata esame in minuti |
| `max_errors` | `3` | Errori massimi consentiti per superare |
| `distribution` | vedi file | Distribuzione domande per categoria (12 × 2 punti + 6 × 1 punto) |

#### `config/mit_import.php` — Import domande da Excel MIT

| Chiave | Default | Descrizione |
|---|---|---|
| `has_header_row` | `true` | Il file Excel ha una riga di intestazione |
| `columns` | vedi file | Mapping colonne → campi DB (codice MIT, testo, risposta, immagine…) |
| `true_values` | vedi file | Valori che vengono interpretati come risposta `true` |
| `topic_map` | vedi file | Mapping codice topic MIT → categoria DB (codici 1–25) |
| `max_rows` | `10000` | Limite righe per import |
| `max_file_size_kb` | `10240` | Limite dimensione file upload |
| `default_license_type_code` | `B` | Tipo di patente assegnato di default alle domande importate |

#### `config/locales.php` — Lingue supportate

| Chiave | Descrizione |
|---|---|
| `default` | Lingua di default (lettura da `APP_LOCALE`) |
| `supported` | Array lingue UI (it/en/es) con label e flag SVG. Aggiungere qui una nuova lingua abilita il dropdown senza modifiche al codice |
| `exam` | Lingue disponibili per la traduzione del **testo delle domande** (it/en/fr/de/es). Aggiungere una entry abilita la lingua nella card "Lingua preferita" del profilo viewer |

#### `config/badges.php` — Badge gamification

Ogni badge è configurato con `name`, `icon`, `description` e soglia.
Modificare i valori soglia qui cambia i requisiti per guadagnare il badge senza toccare la logica di `BadgeService`.

| Badge | Soglia attuale |
|---|---|
| `streak_7` | 7 giorni consecutivi |
| `streak_30` | 30 giorni consecutivi |
| `streak_100` | 100 giorni consecutivi |
| `questions_100` | 100 domande risposte |
| `questions_500` | 500 domande risposte |
| `questions_1000` | 1000 domande risposte |
| `first_pass` | Primo esame simulato superato |
| `all_categories` | Almeno una risposta in ogni categoria |

#### `config/backup.php`

Configurazione di `spatie/laravel-backup`. I valori di retention sono letti dai rispettivi `env()`.
I backup automatici girano alle **01:30** (cleanup) e **02:00** (esecuzione) via scheduler.

---

### Pannello admin — `/admin/system/settings`

Impostazioni persistite su database (tabella `system_settings`), editabili dall'admin
senza toccare `.env` né file di configurazione. Accesso: **Amministratori** → *Impostazioni sistema*.

#### Gruppo `school` — Dati autoscuola

| Chiave | Tipo | Descrizione |
|---|---|---|
| `school.name` | stringa | Nome/ragione sociale (usato in navbar, email, PDF) |
| `school.tagline` | stringa | Slogan mostrato nella homepage guest |
| `school.address` | stringa | Indirizzo sede |
| `school.phone` | stringa | Telefono pubblico |
| `school.email` | stringa | Email pubblica |
| `school.license_number` | stringa | N. autorizzazione MIT |
| `school.logo_path` | path file | Logo modalità chiara (navbar) |
| `school.logo_dark_path` | path file | Logo modalità scura |
| `school.carousel_images` | JSON | Array di immagini carosello homepage (max 4, 1920×600 px) |

#### Gruppo `appearance` — Aspetto

| Chiave | Tipo | Descrizione |
|---|---|---|
| `appearance.accent_color` | colore hex | Colore accent iniettato come `--sg-accent` su tutte le view (default: `#3c8dbc`) |
| `appearance.accent_color_dark` | colore hex | Accent per il tema scuro, iniettato come `--sg-accent-dark` (default: `#4aa3d4`) |
| `appearance.font_family` | select | Font dell'app, iniettato come `--sg-font`: `system`, `inter`, `roboto`, `open-sans`. I font non-`system` caricano il relativo Google Font (default: `system`) |
| `appearance.border_radius` | select | Arrotondamento bordi, iniettato come `--sg-radius`: `square` (0), `default` (.25rem), `rounded` (.5rem) |
| `appearance.sidebar_skin_admin` | select | Skin sidebar AdminLTE per il ruolo admin (default: `sidebar-dark-danger`) |
| `appearance.sidebar_skin_editor` | select | Skin sidebar AdminLTE per il ruolo editor (default: `sidebar-dark-primary`) |
| `appearance.sidebar_skin_viewer` | select | Skin sidebar AdminLTE per il ruolo viewer (default: `sidebar-dark-warning`) |
| `appearance.sidebar_skin_instructor` | select | Skin sidebar AdminLTE per il ruolo instructor (default: `sidebar-dark-success`) |

Le skin sidebar e le variabili CSS sono gestibili da `/admin/system/settings` (gruppo *Aspetto*).
Tutti i valori sono accessibili ovunque nel codice tramite l'helper `setting('chiave')`.
La cache Redis (TTL 3600 s) viene invalidata automaticamente ad ogni salvataggio.

---

### Pannello admin — `/admin/system/health`

Dashboard di monitoraggio in sola lettura. Accesso: **Amministratori** → *Stato sistema*.

| Indicatore | Stato possibile | Note |
|---|---|---|
| Database | Connesso / Non connesso | Test query live |
| Redis | Connesso / Non connesso | Ping al server Redis |
| Queue | OK / Warning / Error | Conteggio job in coda e falliti (`failed_jobs`) |
| Storage | Accessibile / Non accessibile | Verifica permessi `storage/app/public` |
| Mail | OK / Warning / Error | Warning se driver è `log` o `array`; error se SMTP non configurato |
| Twilio SMS | OK / Warning / Error | Warning se `MESSAGING_ENABLED=false` o credenziali mancanti |

Da questa pagina è anche possibile eseguire un **backup immediato** (POST `/admin/health/backup-now`), che viene accodato in background.

---

### Pannello admin — `/admin/commands`

Esecuzione comandi artisan whitelist dall'interfaccia web. Accesso: **Amministratori soltanto**.
Ogni comando mostra exit code, durata, output e timestamp.

#### Gruppo Code

| Comando | Descrizione |
|---|---|
| `queue-emails` | Processa la coda `emails`, si ferma quando è vuota (`--stop-when-empty`) |
| `queue-default` | Processa tutte le code, si ferma quando sono vuote |
| `queue-failed` | Elenca i job falliti |
| `queue-retry-all` | Rimette in coda tutti i job falliti |
| `queue-flush` | ⚠️ Elimina definitivamente tutti i job falliti |

#### Gruppo Cache

| Comando | Descrizione |
|---|---|
| `cache-clear` | Svuota la cache applicazione |
| `config-clear` | Rimuove la cache di configurazione |
| `route-clear` | Rimuove la cache delle route |
| `view-clear` | Rimuove le view Blade compilate |
| `optimize-clear` | Esegue tutti i clear precedenti in un solo comando |

#### Gruppo Sistema

| Comando | Descrizione |
|---|---|
| `migrate-status` | Mostra lo stato delle migration |
| `storage-link` | Crea il symlink `public/storage` (necessario dopo ogni deploy) |
| `about` | Mostra informazioni sull'ambiente (versione PHP, driver, ecc.) |

#### Gruppo Manutenzione

| Comando | Descrizione |
|---|---|
| `enrollments-close-expired` | Chiude le iscrizioni ai quiz con `end_date` passata |

#### Gruppo Push Notifications

| Comando | Descrizione |
|---|---|
| `push-send-review-reminders` | Invia i promemoria SM-2 via web push (eseguito automaticamente alle 08:00) |

#### Gruppo Backup

| Comando | Descrizione |
|---|---|
| `backup-run` | Esegue un backup immediato di DB e storage |
| `backup-clean` | Applica la retention policy ed elimina i backup obsoleti |

#### Gruppo GDPR

| Comando | Input richiesto | Descrizione |
|---|---|---|
| `gdpr-list` | — | Tabella di tutti gli utenti con indicatori di anonimizzazione |
| `gdpr-anonymize-dry-run` | `user_id` | Simulazione anonimizzazione senza effetti reali |
| `gdpr-anonymize` | `user_id` | ⚠️ Anonimizza l'utente (PII mascherati, sessioni/notifiche/documenti eliminati) — **irreversibile** |

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
| [AGENTS.md](AGENTS.md) | Convenzioni operative per Codex e mappa della documentazione |
| [CLAUDE.md](CLAUDE.md) | Convenzioni operative per Claude Code |

---

## Test

Suite con ~611 Feature test in ~51 classi (Laravel TestCase + `RefreshDatabase`):

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
