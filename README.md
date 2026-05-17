# ScuolaGUIDA — Quiz App

Applicazione web per la gestione di quiz della patente di guida. Gli amministratori creano domande, le raggruppano in quiz e gestiscono l'intero ciclo di vita (bozza → pubblicato → confermato); gli utenti si registrano con email/password, completano la propria scheda anagrafica e — una volta approvati dall'amministratore — richiedono l'iscrizione ai quiz ufficiali, li svolgono e consultano le proprie statistiche. È disponibile anche una **Modalità Studio** per esercitarsi liberamente senza timer né punteggio, marcando le domande "da ripassare".

**Stack:** Laravel 11 · Blade · AdminLTE 3 · Bootstrap 5 · Livewire 3 · Alpine.js · MySQL

---

## Installazione

### Prerequisiti

| Tool | Versione minima |
|---|---|
| PHP | 8.3 |
| Composer | 2.x |
| Node.js | 18.x |
| MySQL | 8.x (o MariaDB 10.6+) |

> Con [Laragon](https://laragon.org/) su Windows tutti i prerequisiti sono già inclusi.

### 1. Clona il repository

```bash
git clone <url-repo> scuola-guida
cd scuola-guida
```

### 2. Dipendenze PHP e Node

```bash
composer install
npm install
```

### 3. Configurazione ambiente

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

### 4. Database e dati iniziali

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

### 5. Storage pubblico

```bash
php artisan storage:link
```

Crea il symlink `public/storage → storage/app/public` richiesto per le immagini delle domande.

### 6. Email di notifica (Mailtrap)

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

### 7. Worker della coda email

Le notifiche vengono accodate sulla coda `emails` (driver `database`, già impostato in `.env.example`). In sviluppo lancia il worker in un terminale dedicato:

```bash
php artisan queue:work --queue=emails
```

Il workflow utente non si blocca mai se il worker è spento o se l'SMTP è down: le email sono "fire-and-forget" e verranno processate quando il worker tornerà attivo.

### 8. Scheduler (chiusura automatica iscrizioni scadute)

Il comando `enrollments:close-expired` chiude ogni giorno le iscrizioni `pending` rimaste oltre la data di chiusura impostata sui quiz confermati (vedi *Schedulazione iscrizioni* nell'area admin). È registrato in `routes/console.php` con frequenza `dailyAt('00:05')`.

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

Ogni esecuzione registra in `storage/logs/laravel.log` (`Log::info`) il quiz toccato, il numero di iscrizioni chiuse e la `enrollments_close_at` di riferimento.

### 9. Avvia il server di sviluppo

```bash
# Terminale 1 — asset Vite (hot reload)
npm run dev

# Terminale 2 — server PHP
php artisan serve

# Terminale 3 — worker email (opzionale, vedi sopra)
php artisan queue:work --queue=emails
```

Apri [http://127.0.0.1:8000](http://127.0.0.1:8000), accedi con `admin@test.com` / `password` e vai su `/admin/quizzes`.

### Comandi utili

```bash
php artisan test                    # esegui la test suite
php artisan migrate:fresh --seed    # reset completo del DB
php artisan route:list              # elenco di tutte le route
```

---

## Funzionalità

### Area Admin / Editor

- **Domande** — CRUD, upload immagine, import/export Excel, bulk delete, filtro DataTable
- **Categorie** — CRUD con slug auto-generato
- **Quiz** — creazione manuale o casuale, gestione domande con drag-and-drop reorder, parametri (numero massimo domande, tempo limite, errori massimi tollerati)
- **Ciclo di vita quiz** — `draft → published → confirmed` (vedi sotto)
- **Iscrizioni anagrafiche** — visualizza i dati anagrafici inviati dai viewer (nome, cognome, indirizzo, data e luogo di nascita, codice fiscale, documento di identità), approva o rifiuta la richiesta di iscrizione definitiva con motivazione opzionale
- **Iscrizioni quiz** — approva o rifiuta le richieste degli utenti già abilitati; può riaprire un'iscrizione già completata
- **Schedulazione iscrizioni** — per ogni quiz confermato l'admin può impostare data/ora di **apertura** e di **chiusura** delle iscrizioni (`admin/quizzes/{quiz}/schedule`). Entrambi i campi sono facoltativi: lasciandoli vuoti il quiz mantiene il comportamento attuale. Prima della data di apertura il pulsante "Richiedi iscrizione" è nascosto al viewer (compare il messaggio *"Iscrizioni aperte dal …"*); dopo la data di chiusura compare *"Iscrizioni chiuse"*. Validazione: `enrollments_close_at` deve essere successiva a `enrollments_open_at`. Un comando schedulato giornaliero (`enrollments:close-expired`) sposta in `rejected` le iscrizioni `pending` rimaste oltre la data di chiusura.
- **Riepilogo quiz confermato** (`admin/quizzes/{quiz}/summary`) — pagina dedicata con 4 KPI (totale iscritti, completati, non ancora svolti, punteggio medio) e tabella iscritti ordinata per cognome, colorata per esito (Promosso/Rimandato/Non svolto). Il pulsante "Esporta Excel" in cima alla card scarica un `.xlsx` con i risultati ufficiali (formato pensato per segreteria e istruttori): `Cognome | Nome | Email | Data tentativo | Punteggio | Totale | Percentuale | Esito | Durata (min)`. L'esito è derivato dal `max_errors` del quiz.
- **Esiti confermati** — visualizza i risultati degli utenti sui quiz confermati
- **Statistiche** — dashboard con metriche aggregate (quiz, tentativi, utenti)
- **Media Manager** — gestione file upload (componente Livewire)
- **Audit Log** — storico di ogni create/update/delete con valori prima/dopo
- **Utenti** — CRUD con assegnazione ruolo
- **Ruoli & Permessi** — configura i permessi granulari per ogni ruolo dalla UI

### Area Utente (Viewer)

- **Registrazione account** — email e password (livello base, abilita subito le esercitazioni)
- **Iscrizione anagrafica** — dal proprio profilo il viewer compila nome, cognome, indirizzo, data e luogo di nascita, codice fiscale e carica il documento di identità (PDF/JPG/PNG, max 5 MB), poi invia la richiesta all'amministratore. Solo dopo l'approvazione può iscriversi agli esami ufficiali; può modificare i dati in seguito, ma ogni reinvio richiede una nuova approvazione e disabilita temporaneamente l'iscrizione a nuovi esami
- **Dashboard personale** — statistiche tentativi, punteggio medio, ultima attività
- **Catalogo quiz confermati** — richiedi iscrizione a un quiz ufficiale (riservato ai viewer approvati)
- **Le mie iscrizioni** — traccia lo stato delle richieste (in attesa / approvata / completata)
- **Gioca quiz** — interfaccia a domande con timer e feedback finale (score, errori, esito). Sui quiz ufficiali ogni iscrizione consente un solo tentativo
- **Modalità Studio** — allenamento libero senza timer né punteggio: si scelgono le domande da un quiz pubblicato/confermato, da una categoria oppure casualmente da tutto il database. Per ogni domanda l'utente riceve feedback inline immediato (corretta/errata) e può navigare liberamente avanti e indietro. Ogni domanda può essere marcata come "da ripassare" (stato salvato in sessione, niente DB); al termine il riepilogo mostra totale, risposte date, lista delle marcate e un pulsante per avviare subito una nuova sessione che le contenga
- **Storico tentativi** — rivedi tutti i tuoi quiz svolti
- **Ricerca** — cerca domande per testo o categoria

### Notifiche (email + in-app)

Ogni evento del flusso iscrizioni e dell'amministrazione utenti genera una **doppia notifica**: email (Mailtrap in dev) e in-app (bell nella navbar + pagina `/notifications`).

| Evento | Destinatario | Notification |
|---|---|---|
| Viewer invia dati anagrafici | admin | `NuovaRichiestaAnagrafica` |
| Viewer reinvia dati dopo `approved` | admin | `AnagraficaModificata` |
| Admin approva anagrafica | viewer | `RegistrazioneApprovata` |
| Admin rifiuta anagrafica (motivazione opzionale) | viewer | `RegistrazioneRifiutata` |
| Viewer richiede iscrizione a un quiz | admin | `NuovaIscrizioneQuiz` |
| Admin approva iscrizione quiz | viewer | `IscrizioneQuizApprovata` |
| Admin rifiuta iscrizione quiz (motivazione opzionale) | viewer | `IscrizioneQuizRifiutata` |
| Admin riapre iscrizione completata | viewer | `IscrizioneQuizRiaperta` |
| Viewer completa l'esame | admin | `QuizEsameCompletato` |
| Admin conferma un quiz ufficiale | tutti i viewer approvati | `QuizConfermato` |
| Admin cambia il ruolo di un utente | utente | `RuoloAggiornato` |

**Caratteristiche:**

- **Fire-and-forget** — le notifiche sono `ShouldQueue` sulla coda `emails` (driver `database`). Né uno SMTP irraggiungibile, né un worker spento, né un errore di dispatch interrompono il workflow utente: il `NotificationService` cattura ed esegue il log delle eccezioni e prosegue.
- **Bell Livewire nella navbar** — contatore non-lette, dropdown delle ultime 10 con `markAsRead` al click e redirect alla risorsa correlata, `markAllAsRead`. Componente: `App\Http\Livewire\NotificationBell` integrato via `@section('content_top_nav_right')` in `layouts.admin`.
- **Pagina `/notifications`** — elenco paginato, mark-as-read all'apertura, delete singolo e bulk delete.
- **Template email** in Markdown (`resources/views/emails/*.blade.php`) con header, motivazione condizionale, CTA al portale e footer uniformi.
- **In-app `data`** — ogni notifica scrive un payload JSON standardizzato con `title`, `body`, `url`, `icon`, `color` che il bell consuma per renderizzare l'item.

**Dispatch**: tutto centralizzato nei Service (`UserRegistrationService`, `QuizEnrollmentService`, `QuizService::confirm`, `UserService::update`) tramite l'helper `App\Services\NotificationService`. I controller restano puri.

---

## Ciclo di vita dell'iscrizione anagrafica (viewer)

Solo i viewer hanno un percorso di iscrizione anagrafica con approvazione admin: serve a verificare l'identità prima di consentire la partecipazione agli esami ufficiali. Admin ed editor non sono soggetti a questo flusso (non partecipano agli esami).

```
   [Viewer registra account]
            │
            ▼
        none ──────────────────────────────┐
        (può accedere all'area utente,     │
         non può iscriversi ai quiz)       │
                                           │ Viewer invia
                                           │ dati anagrafici
                                           ▼
                                       pending
                                           │
                              [Admin revisiona richiesta]
                                           │
                              ┌────────────┴────────────┐
                              ▼                         ▼
                           approved                 rejected
                  (abilitato esami)         (può correggere e reinviare)
                              │                         │
                              │   Modifica & reinvia    │   reinvia
                              ▼                         ▼
                           pending  ◀──────────────  pending
                  (perde temporaneamente
                   l'abilitazione fino
                   alla riapprovazione)
```

| Stato | Significato |
|---|---|
| `none` | Account creato ma nessun dato anagrafico inviato. Iscrizione quiz bloccata. |
| `pending` | Dati inviati, in attesa di revisione admin. Iscrizione quiz bloccata. |
| `approved` | Iscrizione definitiva accettata. Il viewer può iscriversi ai quiz ufficiali. |
| `rejected` | Richiesta rifiutata (con motivazione opzionale). Il viewer può correggere e reinviare. |

**Campi obbligatori:** nome, cognome, indirizzo, data di nascita, luogo di nascita, codice fiscale (univoco, validato con regex), documento di identità (PDF/JPG/PNG, max 5 MB, salvato in `storage/app/public/registrations`).

---

## Ciclo di vita di un Quiz

```
     [Admin/Editor]          [Admin]              [Admin]
          │                    │                    │
       Crea quiz            Pubblica             Conferma
          │                    │                    │
          ▼                    ▼                    ▼
       draft  ──────────▶  published  ──────────▶ confirmed
                                                    │
                                    [Viewer richiede iscrizione]
                                                    │
                                                    ▼
                                                 pending
                                                    │
                                    [Admin approva / rifiuta]
                                                    │
                                         ┌──────────┴──────────┐
                                         ▼                     ▼
                                      approved             rejected
                                         │
                                [Viewer gioca il quiz]
                                         │
                                         ▼
                                      completed
```

| Stato | Descrizione |
|---|---|
| `draft` | Visibile solo ad admin/editor; modificabile |
| `published` | Disponibile per il play casuale; non più modificabile |
| `confirmed` | Lock definitivo; aperto alle iscrizioni degli utenti |

---

## Architettura — flusso di una chiamata

Esempio: **aggiornamento di una domanda** (`PUT /admin/questions/{id}`).

Il flusso attraversa cinque strati: **Route → Middleware → FormRequest → Controller → Service → Model/Trait**.

```
Browser
  │
  │  PUT /admin/questions/42
  ▼
┌─────────────────────────────────────────────────────┐
│  routes/web.php                                     │
│  Route::middleware(['auth', 'role:admin,...'])       │
│  → QuestionController@update                        │
└───────────────────────┬─────────────────────────────┘
                        │
                        ▼
┌─────────────────────────────────────────────────────┐
│  UpdateQuestionRequest (FormRequest)                │
│                                                     │
│  authorize()  → verifica permesso 'edit_questions'  │
│  prepareForValidation() → normalizza is_true        │
│  rules() → category_id, question, is_true, image   │
└───────────────────────┬─────────────────────────────┘
                        │ $request->validated()
                        ▼
┌─────────────────────────────────────────────────────┐
│  QuestionController@update                          │
│                                                     │
│  $this->service->update(                            │
│    $question,           ← route model binding       │
│    $request->validated(),                           │
│    $request->file('image')                          │
│  );                                                 │
│  return redirect()->with('success', '...');         │
└───────────────────────┬─────────────────────────────┘
                        │
                        ▼
┌─────────────────────────────────────────────────────┐
│  QuestionService@update                             │
│                                                     │
│  1. Se arriva un nuovo file: deleteImage vecchio,   │
│     storeImage nuovo (storage/app/public/questions) │
│  2. $question->update($data)                        │
└───────────────────────┬─────────────────────────────┘
                        │  evento 'updated' Eloquent
                        ▼
┌─────────────────────────────────────────────────────┐
│  Trait Auditable                                    │
│  Scrive su audit_logs: user_id, event,              │
│  model_type, old_values, new_values                 │
└───────────────────────┬─────────────────────────────┘
                        │  evento 'saved' Eloquent
                        ▼
┌─────────────────────────────────────────────────────┐
│  QuestionObserver@saved                             │
│  clearAdminBadgesCache() → invalida cache sidebar   │
└───────────────────────┬─────────────────────────────┘
                        │
                        ▼
                   redirect 302
               → admin.questions.index + flash
```

### Strati dell'architettura

| Strato | Responsabilità |
|---|---|
| **FormRequest** | Autorizzazione + validazione. Il controller non vede dati non validati. |
| **Controller** | Orchestrazione pura: chiama il service, ritorna la risposta. Nessuna logica. |
| **Service** | Tutta la business logic (11 service: Quiz, QuizAttempt, QuizEnrollment, Question, User, UserRegistration, UserStats, DashboardStats, RolePermission, Search, Study). |
| **Trait Auditable** | Logging automatico di ogni create/update/delete su tutti i modelli che lo usano. |
| **Observer** | Effetti collaterali post-salvataggio (invalidazione cache, ecc.) tenuti fuori dal service. |

---

## Notifiche in-app

Sistema basato sui **Database Notifications nativi di Laravel** — nessuna tabella custom, nessun model custom, nessun WebSocket. Ogni classe `App\Notifications\*` è un canale doppio (`via()` ritorna `['mail', 'database']`): la stessa Notification scrive un record nella tabella `notifications` di Laravel e nello stesso flusso accoda l'email sulla coda `emails`.

### Eventi tracciati

| Evento | Destinatario | Notification |
|---|---|---|
| Viewer invia i dati anagrafici (primo invio o dopo rifiuto) | Tutti gli admin | `NuovaRichiestaAnagraficaNotification` |
| Viewer **re-invia** dati anagrafici dopo essere stato approvato | Tutti gli admin | `AnagraficaModificataNotification` |
| Admin approva iscrizione anagrafica | Viewer interessato | `RegistrazioneApprovataNotification` |
| Admin rifiuta iscrizione anagrafica | Viewer interessato | `RegistrazioneRifiutataNotification` |
| Quiz transita a `confirmed` | Tutti i viewer con `registration_status = approved` | `QuizConfermatoNotification` |
| Viewer richiede iscrizione a un quiz | Tutti gli admin | `NuovaIscrizioneQuizNotification` |
| Admin approva iscrizione quiz | Viewer interessato | `IscrizioneQuizApprovataNotification` |
| Admin rifiuta iscrizione quiz | Viewer interessato | `IscrizioneQuizRifiutataNotification` |
| Admin riapre iscrizione quiz | Viewer interessato | `IscrizioneQuizRiapertaNotification` |
| Viewer completa un esame ufficiale (quiz `confirmed`) | Tutti gli admin | `QuizEsameCompletatoNotification` |
| Admin cambia il ruolo di un utente | Utente interessato | `RuoloAggiornatoNotification` |

Tutti i dispatch passano dal `NotificationService` (`send($notifiables, $notification)` e `sendToAdmins($notification)`): qualsiasi errore di invio viene loggato senza propagare, in modo che il workflow utente non si blocchi mai (vedi i due test `*_redirects_even_if_notification_dispatch_fails`). Le email sono `ShouldQueue` sulla coda `emails`; il record DB invece è sincrono.

### Payload `toDatabase` — contratto unico

Ogni `toDatabase()` restituisce **sempre lo stesso shape**, così la UI renderizza qualunque notifica senza `switch`/`case`:

```php
[
    'title' => 'Iscrizione approvata',           // titolo breve (dropdown + tabella)
    'body'  => 'La tua iscrizione anagrafica...', // testo descrittivo (~50–100 char)
    'url'   => route('dashboard'),                // link al click
    'icon'  => 'fas fa-check-circle',             // icona FontAwesome
    'color' => 'success',                         // success | danger | warning | info
]
```

Convenzione cromatica: approvazioni `success`/`fa-check-circle`, rifiuti `danger`/`fa-times-circle`, eventi pendenti `warning`, eventi informativi (quiz disponibile, riapertura) `info`.

### Campanella in navbar (Livewire)

Componente `App\Http\Livewire\NotificationBell` montato in `layouts/admin.blade.php` via `@section('content_top_nav_right')`, che AdminLTE 3 renderizza dentro `<ul class="navbar-nav ml-auto">` **prima** del menu utente.

- **State**: `unreadCount` (int public). La collection delle ultime 10 notifiche è calcolata in `render()` per restare sempre fresca.
- **Metodi**: `loadNotifications()` (rinfresca il contatore), `markAsRead(string $id)` (`markAsRead` + `$this->redirect($url)` verso il link), `markAllAsRead()` (bulk read sulle `unreadNotifications`).
- **Polling**: `wire:poll.30s="loadNotifications"` sul root `<li>`. Niente Pusher, niente Echo: il contatore si aggiorna ogni 30 s, la query è `limit(10)` per evitare overhead.
- **Visibilità**: l'intero template è dentro `@auth`; gli utenti anonimi non vedono il componente.

> ⚠️ Nessun metodo del componente usa nomi riservati di Livewire 3 (`upload`, `set`, `get`, `call`, …): la Proxy `$wire` aliasa quegli identificatori a magic JS e `wire:click` non invocherebbe il metodo PHP.

### Badge in sidebar

La voce **Notifiche** in `config/adminlte.php` (sezione *AREA PERSONALE*) ha solo `'key' => 'notifications'`. Il badge con il numero di non-lette viene iniettato dal View Composer condiviso descritto in **[Badge della sidebar](#badge-della-sidebar--counter-dellultima-ora)**: il `case 'notifications'` legge `auth()->user()->unreadNotifications()->where('created_at', '>=', $since)->count()`, non cacheato perché per-utente.

### Pagina "Tutte le notifiche"

| Route | Controller | Comportamento |
|---|---|---|
| `GET /notifications` | `NotificationController@index` | Marca tutte le non-lette come lette al load, paginazione 20/pagina ordinata `created_at DESC` |
| `DELETE /notifications/{id}` | `NotificationController@destroy` | `abort_unless` su `notifiable_id`+`notifiable_type` → **403 cross-user** |
| `DELETE /notifications` | `NotificationController@destroyAll` | `$user->notifications()->delete()` — scope limitato all'utente autenticato |

Il controller non contiene business logic: chiama solo i metodi nativi sulla relazione `notifications` esposta dal trait `Notifiable`. La view (`resources/views/notifications/index.blade.php`) usa le classi `sg-*` del design system; le notifiche non-lette sono in `font-weight-bold`; "Elimina tutte" è dietro un `confirm()` JavaScript.

### File chiave

```
app/
  Notifications/                          # 11 classi, tutte con via() = ['mail','database']
    QuizConfermatoNotification.php
    QuizEsameCompletatoNotification.php
    AnagraficaModificataNotification.php
    RuoloAggiornatoNotification.php
    Iscrizione{Quiz*}Notification.php
    Registrazione{Approvata|Rifiutata}Notification.php
    Nuova{IscrizioneQuiz|RichiestaAnagrafica}Notification.php
  Http/
    Livewire/NotificationBell.php         # campanella navbar (Livewire 3)
    Controllers/NotificationController.php
  Services/
    NotificationService.php               # wrapper fire-and-forget
resources/views/
  livewire/notification-bell.blade.php    # dropdown AdminLTE 3 stock
  notifications/index.blade.php           # pagina lista
  emails/quiz-confermato.blade.php        # template markdown email
database/migrations/
  *_create_notifications_table.php        # generata con `php artisan notifications:table`
```

---

## Badge della sidebar — counter dell'ultima ora

I numeri colorati accanto alle voci della sidebar AdminLTE (Domande, Categorie, Quiz, Utenti, Audit Log, Iscrizioni anagrafiche, Notifiche) **non** mostrano il totale assoluto: contano solo gli elementi **aggiunti negli ultimi 60 minuti**. Servono come "novità a colpo d'occhio" per chi entra nel pannello e vuole vedere subito cos'è cambiato di recente.

### Dove vive la logica

Tutto è centralizzato in un unico **View Composer** registrato in `App\Providers\AppServiceProvider::boot()` (sezione *VIEW COMPOSER ADMINLTE*). Il composer è agganciato a `*` (tutte le view) perché il menu della sidebar è renderizzato da AdminLTE su ogni richiesta, prima che venga renderizzata la view di pagina.

### Flusso

```
Request
  │
  ▼
View::composer('*', …)
  │
  │  $since = now()->subHour();
  │
  ├──► Cache::remember('admin_badges', 60, fn () => [
  │      'users'                 => User::where('created_at',  '>=', $since)->count(),
  │      'questions'             => Question::where('created_at', '>=', $since)->count(),
  │      'categories'            => Category::where('created_at', '>=', $since)->count(),
  │      'quizzes'               => Quiz::where('created_at', '>=', $since)->count(),
  │      'audit'                 => AuditLog::where('created_at', '>=', $since)->count(),
  │      'pending_registrations' => User::viewer pending
  │                                   ->where('registration_submitted_at', '>=', $since)
  │                                   ->count(),
  │    ]);
  │
  ├──► $unreadNotifications = auth()->user()
  │      ->unreadNotifications()
  │      ->where('created_at', '>=', $since)
  │      ->count();          // non cacheato: dipende dall'utente loggato
  │
  ▼
config(['adminlte.menu' => …])   // inietta label + label_color per ogni voce con 'key'
```

### Chiavi e mapping

Ogni voce del menu in `config/adminlte.php` espone una `key` (es. `'questions'`, `'registrations'`, `'notifications'`). Il composer fa uno `switch` su quella `key` e assegna `label` e `label_color`. Le voci senza `key` (es. *Profilo*, *Statistiche*) non ricevono badge.

| `key`          | Sorgente                                                                             | Colore     | Note |
|---|---|---|---|
| `questions`    | `Question::where('created_at', '>=', $since)`                                        | `success`  | Sempre visibile (anche con 0) |
| `categories`   | `Category::where('created_at', '>=', $since)`                                        | `info`     | Sempre visibile |
| `quizzes`      | `Quiz::where('created_at', '>=', $since)`                                            | `warning`  | Sempre visibile |
| `users`        | `User::where('created_at', '>=', $since)`                                            | `primary`  | Sempre visibile |
| `audit`        | `AuditLog::where('created_at', '>=', $since)`                                        | `danger`   | Sempre visibile |
| `registrations`| viewer + `REG_PENDING` + `registration_submitted_at >= $since`                       | `warning`  | Visibile solo se > 0 |
| `notifications`| `unreadNotifications()->where('created_at', '>=', $since)`                           | (default)  | Visibile solo se > 0, per-utente |

> Per *Iscrizioni anagrafiche* il timestamp di riferimento è `registration_submitted_at` (momento in cui il viewer ha inviato la richiesta), non `created_at` (che è la registrazione dell'account).

### Cache e invalidazione

- **Cache key:** `admin_badges` — un'unica entry che racchiude tutti i conteggi cross-entity, per ridurre il numero di query a una sola chiamata `Cache::get`.
- **TTL:** 60 secondi. Limite massimo di staleness percepibile dall'utente.
- **Invalidazione esplicita:** ogni Observer (`QuizObserver`, `QuestionObserver`, `CategoryObserver`, `UserObserver`) chiama `clearAdminBadgesCache()` (helper in `app/Helpers/helpers.php`) sui hook `created`/`updated`/`deleted`. Quindi un nuovo elemento appare nel badge entro la prima richiesta successiva, senza attendere lo scadere del TTL.
- **Sliding window:** ogni rinfresco di cache fissa un nuovo `$since = now()->subHour()`. Un elemento creato 59′ fa è ancora contato; quando supera l'ora di vita e il cache miss avviene, sparisce dal badge.

### Note di performance

- Le 6 query del composer girano solo al **cache miss**: ≤ 1 volta al minuto per processo PHP.
- Tutte le `where('created_at', '>=', …)` sfruttano l'indice di default su `created_at` di Laravel; nessun indice aggiuntivo è necessario.
- Il counter `unreadNotifications` non è cacheato per costruzione (è per-utente). Se diventasse un collo di bottiglia, si può spostare in cache `admin_badges_user_{id}` con TTL breve.

### Estendere

Per aggiungere un nuovo badge:

1. Aggiungere la voce al menu in `config/adminlte.php` con una `key` univoca.
2. Aggiungere la query nel composer di `AppServiceProvider` (preferibilmente dentro `Cache::remember`).
3. Aggiungere un `case '<key>':` nello `switch` con `label` e `label_color`.
4. Se la sorgente è un modello nuovo, far chiamare `clearAdminBadgesCache()` dal relativo Observer.

---

## Ruoli e permessi

| Ruolo | Accesso | Iscrizione anagrafica |
|---|---|---|
| `admin` | Tutto: CRUD contenuti, publish/confirm quiz, audit log, gestione utenti e ruoli, approvazione iscrizioni anagrafiche | Non richiesta |
| `editor` | CRUD domande, categorie, quiz (no publish/confirm) | Non richiesta |
| `viewer` | Iscrizione ai quiz confermati solo dopo approvazione dei dati anagrafici | **Obbligatoria** per partecipare ai quiz |

I permessi granulari (`edit_questions`, `delete_quiz`, …) sono configurabili per ruolo dalla pagina **Admin → Ruoli & Permessi** e sono salvati come JSON nel campo `permissions` di ogni utente.

---

## Dipendenze principali

| Package | Uso |
|---|---|
| `jeroennoten/laravel-adminlte` | Template admin con sidebar, navbar, widget |
| `livewire/livewire` | Media Manager (componente dinamico) |
| `maatwebsite/excel` | Import/export domande via Excel |
| `yajra/laravel-datatables` | Tabelle con ricerca/ordinamento server-side |
| `laravel/breeze` | Scaffolding autenticazione (Blade preset) |
| `alpinejs` | Interattività JS leggera (toggle, dropdown) |
| `barryvdh/laravel-debugbar` | Debug toolbar (solo sviluppo) |
