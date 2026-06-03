# Backup automatico e Health dashboard

## Indice

1. [Panoramica](#panoramica)
2. [Configurazione produzione](#configurazione-produzione)
3. [Ripristino da backup](#ripristino-da-backup)
4. [Health dashboard](#health-dashboard)
5. [Verifica integrità da CI/CD](#verifica-integrità-da-cicd)
6. [Notifica fallimento](#notifica-fallimento)

---

## Panoramica

Il backup automatico usa `spatie/laravel-backup` (v9). Ogni notte vengono eseguiti:

- **01:30** — `backup:clean` (rimuove backup secondo la retention policy)
- **02:00** — `backup:run` (crea il nuovo backup)

Il backup include:
- Dump SQL del database MySQL
- Directory `storage/app/public` (media manager: immagini, PDF, video)

Non include: `vendor/`, `node_modules/`, cache, file temporanei.

## Configurazione produzione

### 1. Cron obbligatorio

Aggiungere al crontab del server (una sola riga gestisce tutto lo scheduler):

```cron
* * * * * cd /var/www/scuola-guida && php artisan schedule:run >> /dev/null 2>&1
```

### 2. Variabili `.env` rilevanti

```ini
# Disco locale (storage/app/backups/) — valore di default
BACKUP_DISK=backups

# Retention policy
BACKUP_KEEP_ALL_DAYS=7        # tieni tutti i backup per 7 giorni
BACKUP_KEEP_DAILY=16          # poi 1 al giorno per 16 giorni
BACKUP_KEEP_WEEKLY=8          # poi 1 a settimana per 8 settimane
BACKUP_KEEP_MONTHLY=4         # poi 1 al mese per 4 mesi

# Notifiche (email per gli admin in caso di fallimento)
BACKUP_NOTIFICATION_EMAIL=admin@example.com

# Password archivio zip opzionale
BACKUP_ARCHIVE_PASSWORD=
```

### 3. Disco remoto S3-compatibile (opzionale)

Se si vuole replicare i backup su S3 (o MinIO, Wasabi ecc.):

1. Aggiungere un disco in `config/filesystems.php`:

```php
'backup-s3' => [
    'driver'                  => 's3',
    'key'                     => env('BACKUP_S3_KEY'),
    'secret'                  => env('BACKUP_S3_SECRET'),
    'region'                  => env('BACKUP_S3_REGION', 'eu-west-1'),
    'bucket'                  => env('BACKUP_S3_BUCKET'),
    'endpoint'                => env('BACKUP_S3_ENDPOINT'),
    'use_path_style_endpoint' => true,
    'throw'                   => false,
],
```

2. Aggiungere `'backup-s3'` all'array `destination.disks` in `config/backup.php`.

3. Impostare le variabili nel `.env`:

```ini
BACKUP_S3_BUCKET=my-backups
BACKUP_S3_KEY=...
BACKUP_S3_SECRET=...
BACKUP_S3_REGION=eu-west-1
BACKUP_S3_ENDPOINT=https://s3.example.com   # solo per provider non-AWS
```

## Ripristino da backup

Spatie non fornisce un comando `backup:restore` automatico. Il ripristino è manuale:

### Ripristino database

```bash
# 1. Trova il backup più recente
ls -lh storage/app/backups/<AppName>/

# 2. Estrai il dump SQL
cd /tmp
unzip /var/www/scuola-guida/storage/app/backups/<AppName>/YYYY-MM-DD-HH-II-SS.zip

# 3. Ripristina
mysql -u root -p scuola_guida < /tmp/<AppName>/db-dumps/mysql-scuola_guida.sql
```

### Ripristino media

```bash
# Dal file zip estratto:
cp -r /tmp/<AppName>/storage/app/public/* /var/www/scuola-guida/storage/app/public/
```

## Health dashboard

Accessibile a **solo admin** tramite il menu sidebar "Stato sistema" o direttamente:

```
GET /admin/health
```

Mostra in tempo reale:
- Stato e freschezza dell'ultimo backup
- Dimensione database e top 5 tabelle
- Dimensione media storage
- Spazio disco libero (semaforo: verde > 20%, giallo 10–20%, rosso < 10%)
- Job pendenti per queue e job falliti (con lista espandibile)
- Ultimi 10 errori/critical dal log Laravel

La pagina si ricarica automaticamente ogni 60 secondi.

## Verifica integrità da CI/CD

```bash
php artisan backup:check
# Exit code 0 = backup recente (< 26h) e zip leggibile
# Exit code 1 = nessun backup, backup troppo vecchio, o zip corrotto
```

## Notifica fallimento

In caso di errore durante `backup:run`, tutti gli utenti con ruolo `admin` ricevono:
- Una **notifica in-app** (badge nella campanella)
- Una **email** con il messaggio di errore (path filesystem sanitizzati)

Le notifiche sono accodate su `emails` e sono fire-and-forget (non bloccano il processo).
