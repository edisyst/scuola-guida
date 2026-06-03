# Sistema di notifiche

Ogni evento del flusso iscrizioni e dell'amministrazione utenti genera una **doppia notifica**: email (Mailtrap in dev) e in-app (bell nella navbar + pagina `/notifications`). Il sistema è basato sui **Database Notifications nativi di Laravel** — nessuna tabella custom, nessun model custom, nessun WebSocket.

Ogni classe `App\Notifications\*` è un canale doppio (`via()` ritorna `['mail', 'database']`): la stessa Notification scrive un record nella tabella `notifications` di Laravel e nello stesso flusso accoda l'email sulla coda `emails`.

---

## Indice

1. [Eventi tracciati](#eventi-tracciati)
2. [Caratteristiche](#caratteristiche)
3. [Payload `toDatabase` — contratto unico](#payload-todatabase--contratto-unico)
4. [Campanella in navbar (Livewire)](#campanella-in-navbar-livewire)
5. [Voce sidebar (senza badge)](#voce-sidebar-senza-badge)
6. [Pagina "Tutte le notifiche"](#pagina-tutte-le-notifiche)
7. [File chiave](#file-chiave)
8. [Aggiungere una nuova notifica](#aggiungere-una-nuova-notifica)

---

## Eventi tracciati

| Evento | Destinatario | Notification | Canali |
|---|---|---|---|
| Viewer invia i dati anagrafici (primo invio o dopo rifiuto) | Tutti gli admin | `NuovaRichiestaAnagraficaNotification` | mail + database |
| Viewer **re-invia** dati anagrafici dopo essere stato approvato | Tutti gli admin | `AnagraficaModificataNotification` | mail + database |
| Admin approva iscrizione anagrafica | Viewer interessato | `RegistrazioneApprovataNotification` | mail + database |
| Admin rifiuta iscrizione anagrafica | Viewer interessato | `RegistrazioneRifiutataNotification` | mail + database |
| Quiz transita a `confirmed` | Tutti i viewer con `registration_status = approved` | `QuizConfermatoNotification` | mail + database |
| Viewer richiede iscrizione a un quiz | Tutti gli admin | `NuovaIscrizioneQuizNotification` | mail + database |
| Admin approva iscrizione quiz | Viewer interessato | `IscrizioneQuizApprovataNotification` | mail + database |
| Admin rifiuta iscrizione quiz / chiusura automatica scaduta | Viewer interessato | `IscrizioneQuizRifiutataNotification` | mail + database |
| Admin riapre iscrizione quiz | Viewer interessato | `IscrizioneQuizRiapertaNotification` | mail + database |
| Viewer completa un esame ufficiale (quiz `confirmed`) | Tutti gli admin | `QuizEsameCompletatoNotification` | mail + database |
| Admin cambia il ruolo di un utente | Utente interessato | `RuoloAggiornatoNotification` | mail + database |
| Viewer guadagna un badge | Viewer | `BadgeEarned` | solo database |

---

## Caratteristiche

- **Fire-and-forget** — le notifiche sono `ShouldQueue` sulla coda `emails` (driver `database`). Né uno SMTP irraggiungibile, né un worker spento, né un errore di dispatch interrompono il workflow utente: il `NotificationService` cattura ed esegue il log delle eccezioni e prosegue. Coperto dai due test `*_redirects_even_if_notification_dispatch_fails`.
- **Bell Livewire nella navbar** — contatore non-lette, dropdown delle ultime 10 con `markAsRead` al click e redirect alla risorsa correlata, `markAllAsRead`. Componente: `App\Http\Livewire\NotificationBell` integrato via `@section('content_top_nav_right')` in `layouts.admin`.
- **Pagina `/notifications`** — elenco paginato, mark-as-read all'apertura, delete singolo e bulk delete.
- **Template email** in Markdown (`resources/views/emails/*.blade.php`) con header, motivazione condizionale, CTA al portale e footer uniformi.
- **In-app `data`** — ogni notifica scrive un payload JSON standardizzato con `title`, `body`, `url`, `icon`, `color` che il bell consuma per renderizzare l'item.

**Dispatch**: tutto centralizzato nei Service (`UserRegistrationService`, `QuizEnrollmentService`, `QuizService::confirm`, `UserService::update`, `BadgeService::awardIfEligible`) tramite l'helper `App\Services\NotificationService` (`send($notifiables, $notification)` e `sendToAdmins($notification)`). I controller restano puri. Qualsiasi errore di invio viene loggato senza propagare. Le email sono `ShouldQueue` sulla coda `emails`; il record DB invece è sincrono.

---

## Payload `toDatabase` — contratto unico

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

Convenzione cromatica: approvazioni `success`/`fa-check-circle`, rifiuti `danger`/`fa-times-circle`, eventi pendenti `warning`, eventi informativi (quiz disponibile, riapertura, badge) `info`.

---

## Campanella in navbar (Livewire)

Componente `App\Http\Livewire\NotificationBell` montato in `layouts/admin.blade.php` via `@section('content_top_nav_right')`, che AdminLTE 3 renderizza dentro `<ul class="navbar-nav ml-auto">` **prima** del menu utente.

- **State**: `unreadCount` (int public). La collection delle ultime 10 notifiche è calcolata in `render()` per restare sempre fresca.
- **Metodi**: `loadNotifications()` (rinfresca il contatore), `markAsRead(string $id)` (`markAsRead` + `$this->redirect($url)` verso il link), `markAllAsRead()` (bulk read sulle `unreadNotifications`).
- **Polling**: `wire:poll.30s="loadNotifications"` sul root `<li>`. Niente Pusher, niente Echo: il contatore si aggiorna ogni 30 s, la query è `limit(10)` per evitare overhead.
- **Visibilità**: l'intero template è dentro `@auth`; gli utenti anonimi non vedono il componente.

> ⚠️ Nessun metodo del componente usa nomi riservati di Livewire 3 (`upload`, `set`, `get`, `call`, …): la Proxy `$wire` aliasa quegli identificatori a magic JS e `wire:click` non invocherebbe il metodo PHP.

---

## Voce sidebar (senza badge)

La voce **Notifiche** in `config/adminlte.php` (sezione *AREA PERSONALE*) è una semplice voce di menu senza `label_color`: il contatore non-lette è esposto dalla **campanella in topbar** (`NotificationBell` Livewire), che è renderizzata una volta sola dal layout e si rinfresca via `wire:poll.30s`. Questo evita la query per-utente che il View Composer di sidebar avrebbe dovuto eseguire su ogni view renderizzata.

---

## Pagina "Tutte le notifiche"

| Route | Controller | Comportamento |
|---|---|---|
| `GET /notifications` | `NotificationController@index` | Marca tutte le non-lette come lette al load, paginazione 20/pagina ordinata `created_at DESC` |
| `DELETE /notifications/{id}` | `NotificationController@destroy` | `abort_unless` su `notifiable_id`+`notifiable_type` → **403 cross-user** |
| `DELETE /notifications` | `NotificationController@destroyAll` | `$user->notifications()->delete()` — scope limitato all'utente autenticato |

Il controller non contiene business logic: chiama solo i metodi nativi sulla relazione `notifications` esposta dal trait `Notifiable`. La view (`resources/views/notifications/index.blade.php`) usa le classi `sg-*` del design system; le notifiche non-lette sono in `font-weight-bold`; "Elimina tutte" è dietro un `confirm()` JavaScript.

---

## File chiave

```
app/
  Notifications/                          # 12 classi, di cui 11 con via() = ['mail','database']
    QuizConfermatoNotification.php
    QuizEsameCompletatoNotification.php
    AnagraficaModificataNotification.php
    RuoloAggiornatoNotification.php
    Iscrizione{Quiz*}Notification.php
    Registrazione{Approvata|Rifiutata}Notification.php
    Nuova{IscrizioneQuiz|RichiestaAnagrafica}Notification.php
    BadgeEarned.php                         # guadagno badge — solo canale database
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

## Aggiungere una nuova notifica

1. Crea la classe `App\Notifications\NomeEvento` con `via()` → `['mail', 'database']` e implementa `toMail()` e `toDatabase()` (rispetta il contratto `title/body/url/icon/color`).
2. Aggiungi il template markdown dell'email in `resources/views/emails/`.
3. Aggiungi la classe a `ShouldQueue` + `->onQueue('emails')`.
4. Dispatcha nel **Service** (non nel controller) via `NotificationService::send()` o `NotificationService::sendToAdmins()`.
5. Aggiungi il test in `tests/Feature/NotificationsTest.php`: dispatch, fallback (workflow non si blocca se dispatch fallisce), payload `toDatabase()`.
