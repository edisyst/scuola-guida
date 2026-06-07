# Installazione e setup

Guida operativa per installare e configurare ScuolaGUIDA in ambiente di sviluppo locale. Per la documentazione tecnica delle singole funzionalità vedi [docs/03-features.md](03-features.md) e gli altri file in `docs/`.

---

## Indice

1. [Prerequisiti](#prerequisiti)
2. [1. Clona il repository](#1-clona-il-repository)
3. [2. Dipendenze PHP e Node](#2-dipendenze-php-e-node)
4. [3. Configurazione ambiente](#3-configurazione-ambiente)
5. [4. Database e dati iniziali](#4-database-e-dati-iniziali)
6. [5. Storage pubblico](#5-storage-pubblico)
7. [6. Email di notifica (Mailtrap)](#6-email-di-notifica-mailtrap)
8. [7. Worker della coda email](#7-worker-della-coda-email)
9. [8. Scheduler](#8-scheduler-chiusura-automatica-iscrizioni-scadute)
10. [9. Avvia il server di sviluppo](#9-avvia-il-server-di-sviluppo)
11. [Comandi artisan utili](#comandi-artisan-utili)
12. [Variabili `.env` rilevanti](#variabili-env-rilevanti)
13. [Risoluzione problemi comuni](#risoluzione-problemi-comuni)

---

## Prerequisiti

| Tool | Versione minima |
|---|---|
| PHP | 8.3 |
| Composer | 2.x |
| Node.js | 18.x |
| MySQL | 8.x (o MariaDB 10.6+) |

> Con [Laragon](https://laragon.org/) su Windows tutti i prerequisiti sono già inclusi.

---

## 1. Clona il repository

```bash
git clone <url-repo> scuola-guida
cd scuola-guida
```

## 2. Dipendenze PHP e Node

```bash
composer install
npm install
```

## 3. Configurazione ambiente

```bash
cp .env.example .env
php artisan key:generate
```

Imposta le credenziali del database in `.env`:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=scuola_guida
DB_USERNAME=root
DB_PASSWORD=
```

## 4. Database e dati iniziali

```bash
# Reset completo con dati fittizi (sviluppo locale)
php artisan migrate:fresh --seed

# Solo struttura + dati reali di produzione (admin, ruoli, categorie, domande reali)
php artisan migrate:fresh
php artisan db:seed --class=Database\\Seeders\\ProductionSeeder
```

Il seeder di default crea:
- Utente **admin** — `admin@test.com` / `password`
- Categorie, domande campione, quiz di esempio con tentativi fittizi

### Prerequisito per il seeding di categorie e domande reali

`CategorySeeder` e `QuestionSeeder` (usati da entrambi `DatabaseSeeder` e `ProductionSeeder`) leggono i dati da un file Excel che **non è incluso nel repository** e deve essere posizionato manualmente prima di eseguire qualsiasi seed:

```
storage/app/imports/file_con_category_id.xlsx
```

Il file deve contenere due fogli:

| Foglio | Colonne | Contenuto |
|---|---|---|
| `Categorie` | `category_name`, `category_id` | Le 18 categorie della scuola guida con il loro ID |
| `Domande` | `question`, `is_true`, `image`, `category_id`, `category_name` | Le domande del listato (7143 righe) |

Se il file è assente, i seeder stampano un errore e terminano senza modificare il database.

## 5. Storage pubblico

```bash
php artisan storage:link
```

Crea il symlink `public/storage → storage/app/public` richiesto per le immagini delle domande e i documenti di identità dei viewer.

## 6. Email di notifica (Mailtrap)

Il flusso iscrizioni invia email di cortesia (approvazione/rifiuto anagrafica e quiz, nuove richieste agli admin). In sviluppo conviene usare [Mailtrap](https://mailtrap.io) per intercettarle senza spedirle a indirizzi reali.

1. Crea un inbox gratuito su Mailtrap e copia le credenziali SMTP.
2. In `.env` valorizza la sezione `MAIL_*` (vedi `.env.example`):

```env
MAIL_MAILER=smtp
MAIL_HOST=sandbox.smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=<utente Mailtrap>
MAIL_PASSWORD=<password Mailtrap>
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS="noreply@scuolaguida.local"
MAIL_FROM_NAME="${APP_NAME}"
```

In alternativa, per non spedire nulla, usa `MAIL_MAILER=log` (le email finiscono in `storage/logs/laravel.log`).

## 7. Worker della coda email

Le notifiche vengono accodate sulla coda `emails` (driver `database`, già impostato in `.env.example`). In sviluppo lancia il worker in un terminale dedicato:

```bash
php artisan queue:work --queue=emails
```

Il workflow utente non si blocca mai se il worker è spento o se l'SMTP è down: le email sono "fire-and-forget" e verranno processate quando il worker tornerà attivo.

## 8. Scheduler (chiusura automatica iscrizioni scadute)

Il comando `enrollments:close-expired` chiude ogni giorno le iscrizioni `pending` rimaste oltre la data di chiusura impostata sui quiz confermati. È registrato in `routes/console.php` con frequenza `dailyAt('00:05')`.

**In produzione** basta una singola voce di crontab che esegue lo scheduler di Laravel ogni minuto:

```cron
* * * * * cd /percorso/del/progetto && php artisan schedule:run >> /dev/null 2>&1
```

**In sviluppo**, per verificare il comportamento subito:

```bash
# Esecuzione manuale del singolo comando
php artisan enrollments:close-expired

# Oppure lo scheduler in foreground (esegue i comandi schedulati al momento giusto)
php artisan schedule:work
```

Ogni esecuzione registra in `storage/logs/laravel.log` (`Log::info`) il quiz toccato, il numero di iscrizioni chiuse e la `enrollments_close_at` di riferimento. A ogni iscrizione chiusa viene inviata la notifica `IscrizioneQuizRifiutata` all'utente (fire-and-forget sulla coda `emails`).

## 9. Avvia il server di sviluppo

```bash
# Terminale 1 — asset Vite (hot reload)
npm run dev

# Terminale 2 — server PHP
php artisan serve

# Terminale 3 — worker email (opzionale, vedi sopra)
php artisan queue:work --queue=emails
```

Apri [http://127.0.0.1:8000](http://127.0.0.1:8000), accedi con `admin@test.com` / `password` e vai su `/admin/quizzes`.

---

## Comandi artisan utili

```bash
# Suite di test
php artisan test

# Scaffolding risorsa completo (model + migration + factory + seeder + controller)
php artisan make:model Foo -mfsc

# Livewire
php artisan make:livewire NomeComponente

# Cache produzione
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Comandi custom del progetto
php artisan questions:import-mit /path/file.xlsx                      # import listato MIT (tipo B, default)
php artisan questions:import-mit /path/file.xlsx --license-type=A     # import per tipo A
php artisan questions:import-mit /path/file.xlsx --dry-run            # anteprima senza scrivere
php artisan questions:import-mit /path/file.xlsx --topic=2            # solo argomento 2
php artisan questions:import-mit /path/file.xlsx --update-existing    # aggiorna i duplicati

php artisan license-types:list                   # elenca tipi di patente con statistiche

php artisan enrollments:close-expired           # chiusura manuale iscrizioni scadute
php artisan 2fa:reset {user_id}                 # azzera il 2FA di un admin/editor (recovery)

# Backup e monitoring
php artisan backup:run                          # esegui un backup manuale
php artisan backup:clean                        # rimuovi backup scaduti secondo la policy retention
php artisan backup:check                        # verifica freschezza e integrità ZIP (exit 0 = ok, 1 = problema)

# Guide pratiche
php artisan driving:cleanup-attestations        # rimuove PDF attestazioni non scaricati più vecchi di 24h

# Web Push
php artisan push:send-review-reminders          # invia push SM-2 a viewer con domande in scadenza oggi

# GDPR
php artisan gdpr:list                           # elenco viewer con marker "Anonimizzato"
php artisan gdpr:list --anonymized              # solo i viewer già anonimizzati
php artisan gdpr:anonymize {id} --dry-run       # anteprima
php artisan gdpr:anonymize {id}                 # anonimizzazione definitiva
```

I comandi GDPR e `enrollments:close-expired` sono disponibili anche dal pannello **Admin → Comandi utili** (vedi [docs/03-features.md](03-features.md#comandi-utili)).

---

## Variabili `.env` rilevanti

| Variabile | Default | Note |
|---|---|---|
| `APP_NAME` | `ScuolaGUIDA` | Usato nel `from_name` delle email |
| `APP_URL` | `http://localhost` | Aggiorna in produzione: usato nei link delle email e nel `start_url` del manifest PWA |
| `DB_*` | — | Credenziali MySQL/MariaDB |
| `MAIL_*` | — | SMTP per le notifiche (vedi sezione 6) |
| `QUEUE_CONNECTION` | `database` | Driver coda; usata per la coda `emails` |
| `SESSION_DRIVER` | `database` | Necessario per il logout forzato in `gdpr:anonymize` |
| `CACHE_STORE` | `redis` | Cache `admin_badges`, `user_stats_*`, SM-2, streak, badge |
| `REDIS_HOST` | `127.0.0.1` | Redis per il cache driver; con Laragon avviare Redis dal tray |
| `REDIS_PORT` | `6379` | — |
| `REDIS_CACHE_DB` | `1` | DB Redis separato per la cache (evita collisioni con sessioni) |
| `VAPID_PUBLIC_KEY` | — | Chiave pubblica VAPID per Web Push (Feature 6.7). Generare con `node -e "const wp = require('web-push'); const keys = wp.generateVAPIDKeys(); console.log(keys);"` |
| `VAPID_PRIVATE_KEY` | — | Chiave privata VAPID |
| `VAPID_SUBJECT` | `mailto:admin@example.com` | Email del mittente push (richiesta dal protocollo VAPID) |
| `BACKUP_KEEP_ALL_DAYS` | `7` | Retention backup: mantieni tutti per N giorni |
| `BACKUP_KEEP_DAILY` | `16` | Backup giornalieri da conservare |
| `BACKUP_KEEP_WEEKLY` | `8` | Backup settimanali da conservare |
| `BACKUP_KEEP_MONTHLY` | `4` | Backup mensili da conservare |
| `DRIVING_SCHOOL_NAME` | — | Nome autoscuola — usato nell'header del PDF attestazione guide pratiche |
| `DRIVING_SCHOOL_ADDRESS` | — | Indirizzo autoscuola |
| `DRIVING_SCHOOL_PHONE` | — | Telefono autoscuola |
| `DRIVING_SCHOOL_EMAIL` | — | Email autoscuola |
| `DRIVING_SCHOOL_MIT_AUTH` | — | Numero autorizzazione MIT autoscuola |

---

## Risoluzione problemi comuni

| Sintomo | Causa probabile | Fix |
|---|---|---|
| Pagina di login redirige a `/2fa/setup` | Account admin/editor senza 2FA configurato | Completa il setup o esegui `php artisan 2fa:reset {id}` per disabilitarlo |
| Email non arrivano in Mailtrap | Worker non attivo | Avvia `php artisan queue:work --queue=emails` |
| `Storage::url()` ritorna URL 404 | `storage:link` non eseguito | `php artisan storage:link` |
| Icona PWA placeholder | Le PNG non sono state generate dall'SVG | Vedi [docs/07-pwa.md](07-pwa.md#generazione-icone-png) |
| Service worker non si aggiorna | `CACHE_VERSION` non bumpato | Incrementa `CACHE_VERSION` in `public/sw.js` |
