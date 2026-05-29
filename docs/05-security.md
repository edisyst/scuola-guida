# Sicurezza

Documento di riferimento per i tre meccanismi di sicurezza/compliance del progetto:
- **Ruoli e permessi** — sistema custom (no Spatie)
- **Autenticazione a due fattori (2FA)** — obbligatoria per admin/editor
- **GDPR** — anonimizzazione dati personali (art. 17 GDPR)

---

## Ruoli e permessi

| Ruolo | Accesso | Iscrizione anagrafica |
|---|---|---|
| `admin` | Tutto: CRUD contenuti, publish/confirm quiz, audit log, gestione utenti e ruoli, approvazione iscrizioni anagrafiche | Non richiesta |
| `editor` | CRUD domande, categorie, quiz (no publish/confirm) | Non richiesta |
| `viewer` | Iscrizione ai quiz confermati solo dopo approvazione dei dati anagrafici | **Obbligatoria** per partecipare ai quiz |

I permessi granulari (`edit_questions`, `delete_quiz`, …) sono configurabili per ruolo dalla pagina **Admin → Ruoli & Permessi** e sono salvati come JSON nel campo `permissions` di ogni utente.

![](diagrams/03-roles-pipeline.svg)

### Convenzioni d'uso (importante per chi sviluppa)

- Usa i metodi su `User`: `canEditQuestion()`, `canEditQuiz()`, `canEditCategory()`, `canEditUser()`, `isAdmin()`, `isEditor()`, `isViewer()`.
- **Non** usare Spatie gates né `$user->hasRole()` di Spatie (non è installato).
- Autorizzazione nei controller: `abort_unless(auth()->user()->canEditXxx(), 403)`.
- Le route admin sono in un gruppo `middleware(['auth', '2fa'])`: viewer e ospiti vengono respinti dal middleware prima di arrivare al controller.

---

## Autenticazione a due fattori (2FA)

I ruoli `admin` ed `editor` devono obbligatoriamente configurare il 2FA (TOTP compatibile con Google Authenticator, Authy, ecc.) prima di accedere all'area di gestione. I **viewer** non sono mai coinvolti.

### Installazione dipendenze

```bash
composer require pragmarx/google2fa-laravel bacon/bacon-qr-code
```

### Flusso di configurazione (primo accesso)

1. L'admin/editor effettua il login.
2. Il middleware `EnsureTwoFactorAuthenticated` (alias `'2fa'`) rileva che il 2FA non è configurato e redirige a `/2fa/setup`.
3. La pagina di setup mostra un **QR code SVG inline** (generato server-side, nessuna API esterna) e il secret per inserimento manuale.
4. L'utente inquadra il QR con la propria app TOTP e inserisce l'OTP corrente per confermare.
5. Vengono generati **8 codici di emergenza** (formato `XXXXX-XXXXX`) mostrati una sola volta: l'utente deve salvarli in un posto sicuro.
6. Dopo la conferma, il 2FA è attivo e `2fa_verified = true` viene impostato in sessione.

### Flusso di verifica (login successivi)

1. Dopo il login con email/password, il middleware rileva che il 2FA è attivo ma non verificato (`2fa_verified` assente in sessione).
2. Redirect a `/2fa/challenge` — form OTP con campo `inputmode="numeric"`.
3. In alternativa, un link toggle mostra il form per inserire un **codice di emergenza** (one-time: viene rimosso dall'array dopo l'uso).
4. OTP o codice valido → `2fa_verified = true` in sessione → redirect alla destinazione originale.
5. Al logout, `2fa_verified` viene cancellato dalla sessione.

### Gestione dal profilo (`/profile`)

Nella scheda "Autenticazione a due fattori" (visibile solo ad admin ed editor):

- **Disabilita 2FA** — richiede la password corrente; azzera i tre campi 2FA.
- **Rigenera codici di emergenza** — richiede la password corrente; genera 8 nuovi codici (one-time display).

### Struttura dati

I tre campi su `users` sono tutti nullable e criptati a riposo:

| Campo | Cast | Contenuto |
|---|---|---|
| `two_factor_secret` | `encrypted` | Secret TOTP (Base32) |
| `two_factor_enabled_at` | `datetime` | Timestamp di attivazione |
| `two_factor_recovery_codes` | `encrypted:array` | Array degli 8 codici one-time |

### Recovery (smarrimento dispositivo)

```bash
# Azzera il 2FA dell'utente — l'utente dovrà riconfigurarlo al prossimo accesso
php artisan 2fa:reset {user_id}
```

Il comando logga l'operazione con `Log::info()` e rifiuta di agire se il 2FA non è attivo.

### File chiave

```
app/
  Console/Commands/ResetTwoFactor.php                            # 2fa:reset {user_id}
  Http/Controllers/Auth/TwoFactorChallengeController.php         # show + verify + recovery
  Http/Controllers/Auth/TwoFactorSetupController.php             # setup + codes + disable + regen
  Http/Middleware/EnsureTwoFactorAuthenticated.php               # alias '2fa'
  Models/User.php                                                # casts + hasTwoFactorEnabled + requiresTwoFactor + generateRecoveryCodes
bootstrap/app.php                                                # alias '2fa'
resources/views/auth/two-factor-{challenge,setup,codes}.blade.php
resources/views/profile/partials/two-factor-form.blade.php
routes/web.php                                                   # gruppo 2fa.* + middleware '2fa' su admin
```

---

## GDPR — anonimizzazione dati personali

Due comandi Artisan dedicati permettono di rispondere alle richieste di cancellazione (art. 17 GDPR) **senza** distruggere le statistiche aggregate sui quiz. I dati anagrafici dei viewer (PII) vivono interamente nella tabella `users` — non esiste un profilo separato — quindi l'anonimizzazione opera su un singolo record + due tabelle satellite (`notifications`, `sessions`) + il documento allegato su filesystem.

### Comandi

```bash
# Elenco di tutti i viewer con marker "Anonimizzato" (Sì/No)
php artisan gdpr:list

# Solo i viewer già anonimizzati (filtra per dominio @eliminato.invalid)
php artisan gdpr:list --anonymized

# Anteprima: mostra cosa verrebbe modificato, NON scrive nulla
php artisan gdpr:anonymize 42 --dry-run

# Anonimizzazione definitiva (irreversibile)
php artisan gdpr:anonymize 42
```

Gli stessi comandi sono disponibili dal pannello **Admin → Comandi utili** nel gruppo *GDPR*: tile con input `ID utente` per `gdpr:anonymize` (variante dry-run e variante definitiva, quest'ultima protetta da `confirm()` JS).

### Cosa anonimizza `gdpr:anonymize {id}`

Tutto dentro una `DB::transaction()` (rollback in caso di errore):

| Tabella / risorsa | Operazione |
|---|---|
| `users` | `name` → `"Utente Anonimo {id}"`, `email` → `"anonimo-{id}@eliminato.invalid"` (dominio RFC 2606 — riservato, non risolvibile), `password` rihashata con stringa random da 64 char, `email_verified_at` e `remember_token` azzerati |
| `users` (PII) | `first_name`, `last_name`, `address`, `birth_date`, `birth_place`, `fiscal_code`, `id_document_path` → `null` |
| `users` (registration) | tutti i campi `registration_*` → `null` / `none` |
| `users` (2FA) | `two_factor_secret`, `two_factor_enabled_at`, `two_factor_recovery_codes` → `null` |
| Documento identità | file eliminato dal disk `public` (cartella `registrations/`) |
| `notifications` | tutte le notifiche dell'utente (`$user->notifications()->delete()`) |
| `sessions` | se `SESSION_DRIVER=database`, righe con `user_id` matching cancellate (driver `file`/`redis` → warn esplicito: da invalidare manualmente) |

### Cosa NON tocca

| Tabella | Motivo |
|---|---|
| `quiz_attempts` | Statistiche aggregate (score, durata, risposte) — non contengono PII diretta. Restano collegate al record anonimizzato per preservare gli aggregati storici. |
| `quiz_enrollments` | Storico iscrizioni ai quiz ufficiali — i record puntano all'utente anonimizzato. |
| `audit_logs` | Fuori scope, gestiti a livello infrastrutturale (rotation/retention policy separata). |
| Log applicativi (`storage/logs/laravel.log`) | Fuori scope. |

### Protezioni

- **Utente inesistente** → `$this->error()` + exit code 1.
- **Ruolo `admin`** → blocco esplicito con messaggio + exit code 1. Solo viewer (ed editor, se mai accadesse) possono essere anonimizzati.
- **`--dry-run`** → elenca i campi/contatori/sessioni che verrebbero toccati, zero scritture sul DB.
- **Logging** → `Log::info()` finale con `user_id` / `executor` / `timestamp` / contatori notifiche e documento. **Non** logga la PII originale.
- **Login post-anonimizzazione** → impossibile: la password è hash di stringa random, e l'email originale non esiste più (il record è raggiungibile solo via nuovo dominio `@eliminato.invalid`).

### Note implementative

- La scrittura su `users` passa da `DB::table('users')->update(...)` invece di `User::save()`: il cast `'hashed'` sul campo `password` (definito in `User::casts()`) rihasherebbe automaticamente un valore già hashato, causando un doppio hash e un login indefinitamente impossibile per altri motivi.
- Le notifiche sono Database Notifications native di Laravel (`Notifiable` trait) — la cancellazione passa dalla relazione, niente query manuale sulla tabella `notifications`.
- Test in `tests/Feature/GdprTest.php` (9 test): scenario completo con documento su disk faked, blocco admin, ID inesistente, idempotenza del dry-run su email/fiscal_code/storage/notifiche, login impossibile su entrambe le email, chiusura effettiva delle righe in `sessions` (DB driver), marker corretto in `gdpr:list`, filtro `--anonymized` e empty-state contestuale.
