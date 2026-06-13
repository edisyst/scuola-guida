# Comandi schedulati — ScuolaGUIDA

Tutti i comandi registrati in `routes/console.php` con frequenza e scopo.

| Comando | Frequenza | Scopo |
|---|---|---|
| `backup:clean` | 01:30 ogni notte | Rimuove backup scaduti secondo la policy retention |
| `backup:run` | 02:00 ogni notte | Backup DB + `storage/app/public` |
| `gdpr:export --cleanup-only` | 03:00 ogni notte | Rimuove ZIP export GDPR non scaricati |
| `driving:cleanup-attestations` | 03:30 ogni notte | Rimuove PDF attestazioni guida non scaricati >24h |
| `reports:generate-by-license monthly` | Primo del mese 03:30 | Genera report mensili per tutti i tipi di patente con quiz confermati |
| `enrollments:close-expired` | 00:05 ogni notte | Sposta in `rejected` le iscrizioni `pending` oltre la `enrollments_close_at` |
| `push:send-review-reminders` | 08:00 ogni giorno | Invia push notification SM-2 ai viewer con domande in scadenza oggi |

## Note operative

- In produzione: un singolo crontab `* * * * * cd /path && php artisan schedule:run >> /dev/null 2>&1`
- Il driver coda per i comandi che inviano notifiche è `database` (coda `emails`)
- `backup:check` non è schedulato ma disponibile per CI/CD: exit 0 = ok, exit 1 = problema
