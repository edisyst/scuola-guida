# Installazione e setup

Guida operativa per installare e configurare ScuolaGUIDA in ambiente di sviluppo locale. Per la documentazione tecnica delle singole funzionalità vedi [docs/03-features.md](03-features.md) e gli altri file in `docs/`.

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
php artisan questions:import-mit /path/file.xlsx              # import listato MIT
php artisan questions:import-mit /path/file.xlsx --dry-run    # anteprima senza scrivere
php artisan questions:import-mit /path/file.xlsx --topic=2    # solo argomento 2
php artisan questions:import-mit /path/file.xlsx --update-existing  # aggiorna i duplicati

php artisan enrollments:close-expired           # chiusura manuale iscrizioni scadute
php artisan 2fa:reset {user_id}                 # azzera il 2FA di un admin/editor (recovery)

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
| `CACHE_STORE` | `database` | Cache `admin_badges` e `user_stats_*` |

---

## Risoluzione problemi comuni

| Sintomo | Causa probabile | Fix |
|---|---|---|
| Pagina di login redirige a `/2fa/setup` | Account admin/editor senza 2FA configurato | Completa il setup o esegui `php artisan 2fa:reset {id}` per disabilitarlo |
| Email non arrivano in Mailtrap | Worker non attivo | Avvia `php artisan queue:work --queue=emails` |
| `Storage::url()` ritorna URL 404 | `storage:link` non eseguito | `php artisan storage:link` |
| Icona PWA placeholder | Le PNG non sono state generate dall'SVG | Vedi [docs/07-pwa.md](07-pwa.md#generazione-icone-png) |
| Service worker non si aggiorna | `CACHE_VERSION` non bumpato | Incrementa `CACHE_VERSION` in `public/sw.js` |
