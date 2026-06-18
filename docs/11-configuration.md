# Configurazione e personalizzazione

Questa pagina raccoglie tutti i punti in cui il comportamento dell'applicazione è
modificabile senza toccare il codice: variabili d'ambiente, file `config/`, pannelli
di sistema e comandi artisan eseguibili dall'interfaccia admin.

## Indice

1. [Variabili `.env`](#variabili-env)
2. [File `config/` — opzioni per feature](#file-config--opzioni-per-feature)
3. [Pannello admin — `/admin/system/settings`](#pannello-admin--adminsystemsettings)
4. [Pannello admin — `/admin/system/form-fields`](#pannello-admin--adminsystemform-fields)
5. [Pannello admin — `/admin/system/features`](#pannello-admin--adminsystemfeatures)
6. [Pannello admin — `/admin/system/health`](#pannello-admin--adminsystemhealth)
7. [Pannello admin — `/admin/commands`](#pannello-admin--admincommands)

---

## Variabili `.env`

### Identità applicazione

| Variabile | Default | Descrizione |
|---|---|---|
| `APP_NAME` | `Scuola Guida` | Nome mostrato in navbar, mail e tab del browser |
| `APP_ENV` | `local` | Ambiente (`local` / `production`) |
| `APP_DEBUG` | `true` | Modalità debug (false in produzione) |
| `APP_URL` | `http://localhost` | URL base usato nei link delle notifiche mail |
| `APP_LOCALE` | `it` | Lingua di default dell'interfaccia (`it` / `en` / `es`) |
| `APP_TIMEZONE` | `UTC` | Fuso orario applicazione |

### Autenticazione e sicurezza

| Variabile | Default | Descrizione |
|---|---|---|
| `TWO_FACTOR_ENABLED` | `true` | Obbligo 2FA per admin/editor. `false` = disabilita middleware e nasconde sezione profilo |

### Cache e performance

| Variabile | Default | Descrizione |
|---|---|---|
| `CACHE_ENABLED` | `true` | Master switch cache. `false` = driver `null` (tutto live, utile per debug) |
| `CACHE_STORE` | `redis` | Backend cache (`redis` / `file` / `database` / `memcached`) |
| `REDIS_CLIENT` | `predis` | Client Redis (`predis` = puro PHP, `phpredis` = estensione C) |
| `REDIS_HOST` | `127.0.0.1` | Host Redis |
| `REDIS_PORT` | `6379` | Porta Redis |
| `REDIS_CACHE_DB` | `1` | Database Redis per la cache |

### Code e sessioni

| Variabile | Default | Descrizione |
|---|---|---|
| `QUEUE_CONNECTION` | `database` | Backend code (`database` / `redis` / `beanstalkd`) |
| `SESSION_DRIVER` | `database` | Backend sessioni (`database` / `redis`) |
| `SESSION_LIFETIME` | `120` | Durata sessione in minuti |

### Email

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

### Twilio (health check)

Le variabili Twilio non abilitano invio SMS nell'app — sono usate solo da `SystemHealthService`
per mostrare lo stato dell'integrazione nel pannello `/admin/system/health`.

| Variabile | Default | Descrizione |
|---|---|---|
| `MESSAGING_ENABLED` | `false` | Se `false`, il pannello salute mostra warning Twilio non configurato |
| `TWILIO_ACCOUNT_SID` | — | Account SID Twilio (health check) |
| `TWILIO_AUTH_TOKEN` | — | Auth Token Twilio (health check) |

### Web Push Notifications (VAPID)

| Variabile | Default | Descrizione |
|---|---|---|
| `VAPID_SUBJECT` | `mailto:admin@scuolaguida.local` | Subject VAPID (URL `https://` o email) |
| `VAPID_PUBLIC_KEY` | — | Chiave pubblica VAPID (87 caratteri base64url) |
| `VAPID_PRIVATE_KEY` | — | Chiave privata VAPID (43 caratteri base64url) |

Generazione chiavi: `node -e "const w=require('web-push'); const k=w.generateVAPIDKeys(); console.log(k)"`

### Backup

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

### Media e storage

| Variabile | Default | Descrizione |
|---|---|---|
| `FILESYSTEM_DISK` | `local` | Disco default (`local` / `s3`) |
| `MEDIA_DISK` | `public` | Disco per le immagini delle domande |
| `MEDIA_ACTIVE_DIR` | `test` | Cartella attiva (`test` / `production`) |

### Dati autoscuola (attestazioni guide pratiche)

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

## File `config/` — opzioni per feature

### `config/simulator.php` — Regole esame simulato

| Chiave | Default | Descrizione |
|---|---|---|
| `questions` | `30` | Numero domande per esame |
| `time_limit` | `20` | Durata esame in minuti |
| `max_errors` | `3` | Errori massimi consentiti per superare |
| `distribution` | vedi file | Distribuzione domande per categoria (12 × 2 punti + 6 × 1 punto) |

### `config/mit_import.php` — Import domande da Excel MIT

| Chiave | Default | Descrizione |
|---|---|---|
| `has_header_row` | `true` | Il file Excel ha una riga di intestazione |
| `columns` | vedi file | Mapping colonne → campi DB (codice MIT, testo, risposta, immagine…) |
| `true_values` | vedi file | Valori che vengono interpretati come risposta `true` |
| `topic_map` | vedi file | Mapping codice topic MIT → categoria DB (codici 1–25) |
| `max_rows` | `10000` | Limite righe per import |
| `max_file_size_kb` | `10240` | Limite dimensione file upload |
| `default_license_type_code` | `B` | Tipo di patente assegnato di default alle domande importate |

### `config/locales.php` — Lingue supportate

| Chiave | Descrizione |
|---|---|
| `default` | Lingua di default (lettura da `APP_LOCALE`) |
| `supported` | Array lingue UI (it/en/es) con label e flag SVG. Aggiungere qui una nuova lingua abilita il dropdown senza modifiche al codice |
| `exam` | Lingue disponibili per la traduzione del **testo delle domande** (it/en/fr/de/es). Aggiungere una entry abilita la lingua nella card "Lingua preferita" del profilo viewer |

### `config/badges.php` — Badge gamification

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

### `config/backup.php`

Configurazione di `spatie/laravel-backup`. I valori di retention sono letti dai rispettivi `env()`.
I backup automatici girano alle **01:30** (cleanup) e **02:00** (esecuzione) via scheduler.

---

## Pannello admin — `/admin/system/settings`

Impostazioni persistite su database (tabella `system_settings`), editabili dall'admin
senza toccare `.env` né file di configurazione. Accesso: **Amministratori** → *Impostazioni sistema*.

### Gruppo `school` — Dati autoscuola

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

### Gruppo `appearance` — Aspetto

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

## Pannello admin — `/admin/system/form-fields`

Gestione dei campi dei form di **registrazione** e di **iscrizione anagrafica**
(Feature 13.0), senza toccare il codice. Accesso: **Amministratori** → *Campi form*.
Le impostazioni sono persistite su `system_settings` (gruppo `forms`) e il
`FormFieldService` genera dinamicamente le regole di validazione dai campi abilitati.

Per ogni campo si può controllare:

- **Abilitato** — se il campo viene mostrato e processato nel form
- **Obbligatorio** — se il campo è richiesto (validazione `required`)

| Chiave | Tipo | Descrizione |
|---|---|---|
| `forms.registration_fields` | JSON | Campi del form di registrazione (default: `first_name`, `last_name`, entrambi disabilitati) |
| `forms.enrollment_fields` | JSON | Campi del form di iscrizione anagrafica (default abilitati e obbligatori: nome, cognome, indirizzo, data e luogo di nascita, codice fiscale, documento d'identità) |

Ogni voce dell'array JSON ha la forma
`{ key, label_key, enabled, required, type }`, dove `type` è uno tra
`text`, `date`, `file`. La label è risolta via i18n (`forms.*`) in it/en/es.

---

## Pannello admin — `/admin/system/features`

Feature toggle gestibili da back office (Feature 13.2): abilitano o disabilitano
intere funzionalità senza deploy. Accesso: **Amministratori** → *Funzionalità*.
Il `FeatureToggleService` espone `isEnabled()`, l'helper globale `feature('chiave')`
e distingue i toggle persistiti su DB (switch on/off in tempo reale via `wire:change`)
da quelli gestiti via file di configurazione (sezione read-only).

| Chiave | Default | Funzionalità controllata |
|---|---|---|
| `features.gamification_enabled` | on | Gamification (badge e streak) |
| `features.web_push_enabled` | on | Notifiche Web Push |
| `features.guest_homepage_enabled` | on | Homepage pubblica guest |
| `features.exam_translations_enabled` | on | Selezione lingua interfaccia |
| `features.driving_practice_enabled` | on | Modulo guide pratiche |
| `features.eu_categories_visible` | on | Categorie EU nello studio |
| `features.study_content_enabled` | on | Contenuti formativi StudyContent |

I check `feature()` sono applicati nei controller delle rispettive aree
(gamification, web push, homepage guest, traduzioni esame, guide pratiche,
categorie EU, study content). Le label sono tradotte via i18n (`features.*`).

---

## Pannello admin — `/admin/system/health`

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

## Pannello admin — `/admin/commands`

Esecuzione comandi artisan whitelist dall'interfaccia web. Accesso: **Amministratori soltanto**.
Ogni comando mostra exit code, durata, output e timestamp.

### Gruppo Code

| Comando | Descrizione |
|---|---|
| `queue-emails` | Processa la coda `emails`, si ferma quando è vuota (`--stop-when-empty`) |
| `queue-default` | Processa tutte le code, si ferma quando sono vuote |
| `queue-failed` | Elenca i job falliti |
| `queue-retry-all` | Rimette in coda tutti i job falliti |
| `queue-flush` | ⚠️ Elimina definitivamente tutti i job falliti |

### Gruppo Cache

| Comando | Descrizione |
|---|---|
| `cache-clear` | Svuota la cache applicazione |
| `config-clear` | Rimuove la cache di configurazione |
| `route-clear` | Rimuove la cache delle route |
| `view-clear` | Rimuove le view Blade compilate |
| `optimize-clear` | Esegue tutti i clear precedenti in un solo comando |

### Gruppo Sistema

| Comando | Descrizione |
|---|---|
| `migrate-status` | Mostra lo stato delle migration |
| `storage-link` | Crea il symlink `public/storage` (necessario dopo ogni deploy) |
| `about` | Mostra informazioni sull'ambiente (versione PHP, driver, ecc.) |

### Gruppo Manutenzione

| Comando | Descrizione |
|---|---|
| `enrollments-close-expired` | Chiude le iscrizioni ai quiz con `end_date` passata |

### Gruppo Push Notifications

| Comando | Descrizione |
|---|---|
| `push-send-review-reminders` | Invia i promemoria SM-2 via web push (eseguito automaticamente alle 08:00) |

### Gruppo Backup

| Comando | Descrizione |
|---|---|
| `backup-run` | Esegue un backup immediato di DB e storage |
| `backup-clean` | Applica la retention policy ed elimina i backup obsoleti |

### Gruppo GDPR

| Comando | Input richiesto | Descrizione |
|---|---|---|
| `gdpr-list` | — | Tabella di tutti gli utenti con indicatori di anonimizzazione |
| `gdpr-anonymize-dry-run` | `user_id` | Simulazione anonimizzazione senza effetti reali |
| `gdpr-anonymize` | `user_id` | ⚠️ Anonimizza l'utente (PII mascherati, sessioni/notifiche/documenti eliminati) — **irreversibile** |
