# Funzionalità

Catalogo delle funzionalità esposte all'utente, distinte per ruolo (admin/editor vs viewer), con i flussi di vita degli oggetti principali (anagrafica, quiz, iscrizioni).

Riferimenti incrociati:
- [Architettura](02-architecture.md) — flussi tecnici cross-layer
- [Sicurezza](05-security.md) — 2FA, GDPR, ruoli & permessi
- [Notifiche](04-notifications.md) — dispatch email + in-app
- [Modalità studio & simulatore](06-study-and-simulator.md) — dettagli tecnici
- [PWA](07-pwa.md) — offline e installazione

---

## Indice

1. [Area Admin / Editor](#area-admin--editor)
   - [Comandi utili](#comandi-utili)
   - [Badge sidebar](#badge-sidebar--counter-dellultima-ora)
2. [Area Utente (Viewer)](#area-utente-viewer)
3. [Ciclo di vita dell'iscrizione anagrafica (viewer)](#ciclo-di-vita-delliscrizione-anagrafica-viewer)
4. [Ciclo di vita del Quiz](#ciclo-di-vita-del-quiz)
5. [Dashboard utente — dettaglio tecnico](#dashboard-utente--dettaglio-tecnico)

---

## Area Admin / Editor

- **Domande** — CRUD, upload immagine, import/export Excel, import listato MIT, bulk delete, filtro DataTable
- **Categorie** — CRUD con slug auto-generato; gestione **materiale didattico** per categoria (PDF, link/YouTube, note testuali) con drag-and-drop per il riordinamento (`admin/categories/{id}/materials`)
- **Traduzioni domande** (`admin/questions/{question}/translations`) — pulsante "Traduzioni" nella DataTable domande (visibile a `canEditQuestion()`). Admin/editor caricano la traduzione del **testo** della domanda nelle lingue d'esame configurate in `config/locales.php` → `exam` (`it`, `en`, `fr`, `de`, `es`). Una sola traduzione per lingua per domanda (indice unico). Il testo italiano resta la fonte di verità; le traduzioni sono entità separate e **non** rientrano nel versionamento (Feature 6.2). Fallback automatico all'italiano se la traduzione manca (Feature 7.1)
- **Quiz** — creazione manuale o casuale, gestione domande con drag-and-drop reorder, parametri (numero massimo domande, tempo limite, errori massimi tollerati)
- **Ciclo di vita quiz** — `draft → published → confirmed` (vedi *Ciclo di vita del quiz*)
- **Iscrizioni anagrafiche** — visualizza i dati anagrafici inviati dai viewer (nome, cognome, indirizzo, data e luogo di nascita, codice fiscale, documento di identità), approva o rifiuta la richiesta di iscrizione definitiva con motivazione opzionale
- **Iscrizioni quiz** — approva o rifiuta le richieste degli utenti già abilitati; può riaprire un'iscrizione già completata
- **Catalogo "Quiz disponibili" in sola lettura** — admin ed editor non partecipano agli esami ufficiali, ma possono comunque consultare il catalogo (`quiz/confirmed`) per verificare la lista dei quiz confermati. Le voci di menu "Le mie iscrizioni" e "I miei tentativi" sono riservate ai viewer (gate `exam-participant`); nel catalogo le colonne "Stato iscrizione" e "Azioni" sono nascoste per i non-viewer e compare un banner *"Visualizzazione in sola lettura"*
- **Schedulazione iscrizioni** — l'admin può impostare data/ora di **apertura** e di **chiusura** delle iscrizioni sia alla creazione del quiz sia in seguito dalla pagina dedicata (`admin/quizzes/{quiz}/schedule`). Entrambi i campi sono facoltativi: lasciandoli vuoti il quiz mantiene il comportamento standard. Prima della data di apertura il pulsante "Richiedi iscrizione" è nascosto al viewer (compare il messaggio *"Iscrizioni aperte dal …"*); dopo la data di chiusura compare *"Iscrizioni chiuse"*. Validazione: `enrollments_close_at` deve essere successiva a `enrollments_open_at`. Un comando schedulato giornaliero (`enrollments:close-expired`) sposta in `rejected` le iscrizioni `pending` rimaste oltre la data di chiusura e invia la notifica `IscrizioneQuizRifiutata` a ogni utente coinvolto
- **Riepilogo quiz confermato** (`admin/quizzes/{quiz}/summary`) — pagina dedicata accessibile dal pulsante `fas fa-chart-bar` nella lista quiz, solo per quiz `confirmed`. Quattro `small-box` AdminLTE: *Totale iscritti* (blu), *Hanno completato* (verde), *Non ancora svolto* (giallo), *Punteggio medio* (verde acqua — 1 decimale + `%`, oppure `—` se nessuno ha completato). Tabella iscritti ordinata per cognome con colonne Punteggio, Percentuale, Esito e Data tentativo; righe colorate `table-success` / `table-danger` / `table-warning` in base all'esito. Logica di aggregazione interamente in `QuizSummaryService::getSummary()` (eager loading, zero N+1). Il pulsante "Esporta Excel" (visibile agli utenti con `canEditQuiz()`) scarica un `.xlsx` con i risultati ufficiali
- **Esiti confermati** — visualizza i risultati degli utenti sui quiz confermati
- **Segnalazioni domande** (`admin/question-reports`) — pannello di moderazione per i report inviati dai viewer (icona bandiera in sidebar, badge `warning` con il numero di pending). KPI in cima (pending/accettate/rifiutate) cliccabili come filtro rapido; barra filtri per stato, tipologia e ID domanda; tabella con segnalante, data, tipo (badge colorato) e link al dettaglio. Pagina di dettaglio a due colonne: a sinistra la domanda segnalata (testo, immagine, risposta corretta, link "Modifica domanda"), a destra i dati del report e il form Alpine per accettare/rifiutare con nota opzionale al segnalante; pulsante elimina per i casi di spam. Accept/reject tracciano `resolved_by` e `resolved_at`. Accesso protetto da gate `view-question-reports` (risolve a `canEditQuestion()`)
- **Statistiche** — dashboard con metriche aggregate (quiz, tentativi, utenti)
- **Media Manager** — gestione file upload (componente Livewire)
- **Audit Log** — storico di ogni create/update/delete con valori prima/dopo
- **Comandi utili** — vedi sezione [Comandi utili](#comandi-utili) più sotto
- **Autenticazione a due fattori (2FA)** — obbligatoria per `admin` ed `editor`; dettagli in [docs/05-security.md](05-security.md#autenticazione-a-due-fattori-2fa)
- **Utenti** — CRUD con assegnazione ruolo
- **Ruoli & Permessi** — configura i permessi granulari per ogni ruolo dalla UI
- **Tipi di patente** (`admin/license-types`) — CRUD dei 17 tipi di patente italiani (AM, A1, A2, A, B, B96, BE, C1, C1E, C, CE, D1, D1E, D, DE, CQC Merci, CQC Persone); ogni tipo ha un formato esame personalizzato (n. domande, minuti, errori max) e un elenco di categorie associate. Gestito via `LicenseTypeService`; autorizzazione `canEditLicenseType()` (solo admin). Vedi [Feature 8.0 nel CHANGELOG](../CHANGELOG.md)
- **Moduli di guida pratica** (`admin/driving-modules`) — CRUD moduli MIT per tipo di patente (codice, ore richieste, ordine); ogni tipo di patente può avere moduli diversi. Un modulo non è eliminabile finché esistono sessioni registrate (`DrivingModuleService::delete()` con guard). Voce sidebar "Moduli guida pratica" nella sezione CATALOGO
- **Sessioni di guida pratica** — istruttori e admin registrano le sessioni dei singoli studenti (modulo, data, durata, note). Gestite da `DrivingSessionService`; route `/driving/sessions` con middleware `role:admin,instructor`. L'admin può accedere a tutti gli studenti; l'istruttore solo ai propri assegnati
- **Area istruttore** — il ruolo `instructor` vede i progressi degli studenti assegnati (statistiche quiz, streak, badge, sessioni guida); può aggiungere note testuali sullo studente (max 2000 caratteri) e riceve una notifica automatica (`InstructorStudentOutcome`: mail + in-app + push) al completamento di ogni quiz. Può esportare un PDF riassuntivo (KPI, tentativi, badge, note, sessioni guida) tramite `DrivingAttestationService`. Admin assegnano gli studenti via `admin/instructors/{instructor}/assignments`
- **Dashboard editor** (`/editor/dashboard`) — KPI di produzione contenuti (domande create/modificate, quiz pubblicati/confermati, attività giornaliera), stato globale contenuti (categorie per domande, top segnalate, quiz per stato, domande senza immagine, ultime segnalazioni). Filtro per periodo e per tipo di patente; selettore editor per admin. `EditorMetricsService` con cache TTL 86400 per periodi passati

### Comandi utili

Pannello `admin/commands` (solo admin): tile con pulsanti per lanciare da web una whitelist di comandi `php artisan` divisa in quattro gruppi:

- **Code** — `queue:work --stop-when-empty`, `queue:failed`, `queue:retry`, `queue:flush`
- **Cache** — `cache:clear`, `config:clear`, `route:clear`, `view:clear`, `optimize:clear`
- **Sistema** — `migrate:status`, `storage:link`, `about`
- **GDPR** — `gdpr:list`, `gdpr:anonymize {id} --dry-run`, `gdpr:anonymize {id}` (vedi [docs/05-security.md](05-security.md#gdpr--anonimizzazione-dati-personali))

Esecuzione sincrona con cattura di exit code, durata e output, mostrati in un pannello in cima alla pagina. I comandi long-running come `queue:work` sono lanciati con `--stop-when-empty` per terminare entro la request — la UI non avvia daemon. I comandi che richiedono argomenti (es. `gdpr:anonymize {id}`) hanno un input dedicato nella tile, validato lato server.

### Badge sidebar — counter dell'ultima ora

I numeri colorati accanto alle voci della sidebar AdminLTE (Domande, Categorie, Quiz, Utenti, Audit Log, Iscrizioni anagrafiche, Segnalazioni) **non** mostrano il totale assoluto: contano solo gli elementi **aggiunti negli ultimi 60 minuti** (eccezione: *Segnalazioni* mostra il totale pending senza filtro temporale). Servono come "novità a colpo d'occhio" per chi entra nel pannello.

> Il contatore **Notifiche** non vive qui: è esposto dalla campanella in topbar (componente `NotificationBell`) — vedi [docs/04-notifications.md](04-notifications.md).

Tutto è centralizzato in un unico **View Composer** registrato in `App\Providers\AppServiceProvider::boot()`. Il composer è agganciato a `layouts.admin` (Feature 6.7 ha chiuso il known issue del `View::composer('*', ...)` che girava su ogni view dell'applicazione).

**Chiavi e mapping**:

| `key`             | Sorgente                                                              | Colore    | Note |
|---|---|---|---|
| `questions`       | `Question::where('created_at', '>=', $since)`                         | `success` | Visibile solo se > 0 |
| `categories`      | `Category::where('created_at', '>=', $since)`                         | `info`    | Visibile solo se > 0 |
| `quizzes`         | `Quiz::where('created_at', '>=', $since)`                             | `warning` | Visibile solo se > 0 |
| `users`           | `User::where('created_at', '>=', $since)`                             | `primary` | Visibile solo se > 0 |
| `audit`           | `AuditLog::where('created_at', '>=', $since)`                         | `danger`  | Visibile solo se > 0 |
| `registrations`   | viewer + `REG_PENDING` + `registration_submitted_at >= $since`        | `warning` | Visibile solo se > 0 |
| `question-reports`| `QuestionReport::pending()->count()` — senza filtro temporale         | `warning` | Sempre actionable, mostra il totale |

**Cache e invalidazione**:

- **Cache key:** `admin_badges` — un'unica entry che racchiude tutti i conteggi cross-entity.
- **TTL:** 60 secondi.
- **Invalidazione esplicita:** ogni Observer (`QuizObserver`, `QuestionObserver`, `CategoryObserver`, `UserObserver`) chiama `clearAdminBadgesCache()` (helper in `app/Helpers/helpers.php`) sui hook `created`/`updated`/`deleted`. Un nuovo elemento appare nel badge entro la prima richiesta successiva, senza attendere lo scadere del TTL.
- **Sliding window:** ogni rinfresco di cache fissa un nuovo `$since = now()->subHour()`.

**Estendere**:

1. Aggiungi la voce al menu in `config/adminlte.php` con una `key` univoca.
2. Aggiungi la query nel composer di `AppServiceProvider` (preferibilmente dentro `Cache::remember`).
3. Aggiungi un `case '<key>':` nello `switch` con `label` e `label_color`.
4. Se la sorgente è un modello nuovo, far chiamare `clearAdminBadgesCache()` dal relativo Observer.

---

## Area Utente (Viewer)

- **Registrazione account** — email e password (livello base, abilita subito le esercitazioni)
- **Iscrizione anagrafica** — dal proprio profilo il viewer compila nome, cognome, indirizzo, data e luogo di nascita, codice fiscale e carica il documento di identità (PDF/JPG/PNG, max 5 MB), poi invia la richiesta all'amministratore. Solo dopo l'approvazione può iscriversi agli esami ufficiali. Vedi [Ciclo di vita iscrizione anagrafica](#ciclo-di-vita-delliscrizione-anagrafica-viewer)
- **Dashboard personale** — statistiche tentativi con cache 10 minuti (`UserStatsService`), invalidata automaticamente ad ogni nuovo tentativo tramite `QuizAttempt::booted()`. Vedi [Dashboard utente](#dashboard-utente--dettaglio-tecnico)
- **Catalogo quiz confermati** — richiedi iscrizione a un quiz ufficiale (riservato ai viewer approvati)
- **Calendario sessioni** (`GET /calendar`) — lista cronologica di tutti i quiz confermati divisa in tre sezioni: *Prossime sessioni* (iscrizioni non ancora aperte, con countdown Alpine.js), *Iscrizioni aperte* (finestra attiva o senza date), *Sessioni chiuse* (ultime 10). Il widget "Prossima sessione" appare anche nella dashboard personale, mostrando il quiz più vicino tra aperti e upcoming
- **Le mie iscrizioni** — traccia lo stato delle richieste (in attesa / approvata / completata)
- **Gioca quiz** — interfaccia a domande con timer e feedback finale (score, errori, esito). Sui quiz ufficiali ogni iscrizione consente un solo tentativo
- **Dettaglio tentativo** (`/quiz/attempts/{id}`) — pagina completa di revisione post-quiz: card riepilogo verde/rossa (PROMOSSO/RIMANDATO) con 6 KPI (punteggio, percentuale, errori/max, non risposto, durata, data) e barra di progresso; una card per ogni domanda con bordo colorato (verde = corretta, rosso = errata, arancione = non risposta), categoria, risposta utente vs corretta, tempo speso per domanda e immagine opzionale. Protezione IDOR: ogni viewer vede solo i propri tentativi; admin e utenti con `canEditUser()` possono consultare qualsiasi tentativo (con banner informativo)
- **Simulatore Esame** (`GET /simulator`) — riproduce il formato ufficiale dell'esame di teoria patente B. Vedi [docs/06-study-and-simulator.md](06-study-and-simulator.md#simulatore-esame)
- **Modalità Studio** — allenamento libero senza timer né punteggio. Vedi [docs/06-study-and-simulator.md](06-study-and-simulator.md#modalità-studio)
- **Domande salvate (Bookmark)** — `GET /bookmarks`. Il viewer può aggiungere o rimuovere il segnalibro su qualsiasi domanda direttamente dalla revisione del tentativo e dalla modalità studio, tramite il componente Livewire `BookmarkButton`. Ogni bookmark può avere una **nota personale** (max 500 caratteri) opzionale, modificabile inline. Il pulsante "Studia le domande salvate" avvia una sessione studio sulle sole domande bookmarkate
- **Segnala errori sulle domande** — pulsante "Segnala" integrato in tutte le view di gioco e revisione. Componente Livewire `ReportButton` con form collassabile inline: select tipologia (risposta errata, testo ambiguo, immagine mancante, contenuto obsoleto, altro) + textarea descrizione (min 10, max 1000 caratteri). Anti-spam: massimo 3 segnalazioni pending per stesso viewer e stessa domanda
- **Revisione errori** (`GET /review-errors`) — aggrega le domande sbagliate del viewer negli ultimi N tentativi completati (configurabile: 10/20/30/50, default 20). Per ogni domanda: badge categoria, badge "Sbagliata X volte" (colorato), data ultimo sbaglio relativa. Filtro per categoria e toggle "Mostra solo le imparate". Il pulsante **"Marca come imparata"** esclude la domanda dalla lista; "Reinserisci negli errori" la rimette in coda
- **Test diagnostico** (`GET /diagnostic`) — sequenza di domande rapida (una per categoria attiva), svolgibile al primo accesso o on-demand dalla dashboard. Le domande risposte nelle ultime 24h vengono escluse automaticamente. Ogni sessione è salvata come gruppo (`batch_id`) in `diagnostic_results` per poter essere confrontata con sessioni future
- **Piano di studio** (`GET /study-plan`) — lista di tutte le categorie ordinata per "debolezza" (mastery ascendente). Per ogni categoria: punteggio di padronanza 0–100 (derivato dai dati storici, dal diagnostico o da entrambi con peso 70%/30%), contatore tentativi e stringa `recommended_action` con tre livelli ("Inizia con questa categoria" / "Continua a esercitarti" / "Padronanza buona, ripassa occasionalmente"). Pulsante "Studia ora" che avvia direttamente la modalità studio sulla categoria
- **Ripasso intelligente** (`GET /smart-review`) — sessioni di ripasso basate sull'algoritmo SM-2 semplificato. Ogni risposta data in modalità studio o in un quiz aggiorna automaticamente l'intervallo di revisione della domanda (cap 365 giorni). La pagina panoramica mostra 4 statistiche (tracciate/padroneggiata/in apprendimento/da iniziare) e il numero di domande in scadenza oggi/domani/settimana. Il badge nella sidebar mostra il numero di domande in scadenza oggi
- **Gamification — Streak e Badge** (`GET /profile/badges`) — sistema di riconoscimenti personali: la **streak** conta i giorni consecutivi di studio (aggiornata automaticamente ad ogni risposta in modalità studio, quiz o simulatore) ed è visibile nella dashboard con avviso "A rischio" se non si è ancora studiato oggi. I **badge** vengono assegnati automaticamente al raggiungimento di milestone: `streak_7/30/100`, `questions_100/500/1000`, `first_pass` (primo simulatore promosso), `all_categories`. Al guadagno di ogni badge viene inviata una **notifica in-app** (`BadgeEarned`, canale solo `database`). Tutte le definizioni badge sono in `config/badges.php` — nessun valore hardcoded nel codice
- **Lingua preferita delle domande** (Feature 7.1) — card "Lingua preferita" nel profilo: il viewer sceglie in quale lingua visualizzare il **testo delle domande** in modalità studio, simulatore e test diagnostico. Le lingue disponibili sono quelle configurate in `config/locales.php` → `exam`. Se la traduzione manca, viene mostrato automaticamente il testo originale italiano (fallback garantito). La preferenza è salvata in `users.locale` (`null` = italiano di default). Concetto distinto dalla lingua dell'interfaccia (vedi [Localizzazione nel README](../README.md#localizzazione))
- **Patente in studio** (Feature 8.1) — card nel profilo viewer con select dei tipi di patente attivi; la scelta è salvata in `users.active_license_type_id`. Studio, simulatore, diagnostico e ripasso SM-2 vengono filtrati per le domande delle categorie associate al tipo scelto. Il formato esame (n. domande, minuti, errori max) è quello del tipo selezionato. Il middleware `RequireLicenseType` reindirizza al profilo i viewer che non hanno ancora scelto un tipo
- **Avanzamento guide pratiche** (`GET /driving/progress`) — il viewer vede il proprio avanzamento nelle ore di guida pratica obbligatorie: ore completate/richieste per ogni modulo, percentuale globale. Il calcolo avviene in due query (moduli + sessioni raggruppate) senza N+1
- **Export PDF attestazione guide pratiche** — quando il viewer ha completato tutte le ore obbligatorie, può scaricare un PDF con intestazione autoscuola, dati studente, riepilogo per modulo e dettaglio sessioni. L'istruttore e l'admin possono esportare il PDF per qualsiasi studente. Il file è generato on-demand e rimosso al download; cleanup automatico alle 03:30 per file non scaricati. Route `GET /driving/students/{student}/attestation`
- **Progressive Web App (PWA)** — l'applicazione è installabile come app nativa sul dispositivo e supporta la modalità studio offline. Vedi [docs/07-pwa.md](07-pwa.md)
- **Storico tentativi** — elenco paginato di tutti i quiz svolti con link al dettaglio
- **Ricerca** — cerca domande per testo o categoria dalla barra della navbar; i risultati si aprono in una nuova scheda

---

## Ciclo di vita dell'iscrizione anagrafica (viewer)

![](diagrams/04-quiz-lifecycle.svg)

Solo i viewer hanno un percorso di iscrizione anagrafica con approvazione admin: serve a verificare l'identità prima di consentire la partecipazione agli esami ufficiali. Admin ed editor non sono soggetti a questo flusso.

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

## Ciclo di vita del Quiz

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

## Dashboard utente — dettaglio tecnico

La dashboard personale (`GET /dashboard`) è servita dal `UserStatsController::me()`:

- Gli **admin ed editor** vedono i KPI globali (`DashboardStatsService::kpi()`): totale utenti, domande, categorie, quiz + grafici `dailyCreated` degli ultimi 30 giorni (Chart.js line).
- I **viewer** vedono le proprie statistiche aggregate (`UserStatsService::get($user)`): tutto viene calcolato con un batch di query SQL e salvato in cache `user_stats_{id}` per 10 minuti (`CACHE_TTL = 600`).

### Dati calcolati per viewer

| Metrica | Fonte |
|---|---|
| `total_attempts` | `COUNT(*)` su `quiz_attempts` |
| `total_correct` / `total_questions` | `SUM(score)` / `SUM(total_questions)` |
| `avg_percentage` / `best_percentage` / `worst_percentage` | `AVG`/`MAX`/`MIN` su `(score*100/total_questions)` |
| `passed_count` / `failed_count` / `pass_rate` | soglia 60% |
| `avg_duration` / `total_duration` | `AVG`/`SUM(duration)` |
| `latest_attempts` | ultimi 10 con eager load `quiz:id,title` |
| `daily_chart` | `COUNT` e `AVG %` per giorno, ultimi 30 gg |
| `avg_by_quiz` | top-10 quiz per numero di tentativi, con media e best % |

### Invalidazione cache

Il model `QuizAttempt` usa `static::booted()` per chiamare `UserStatsService::forget($userId)` su `saved` e `deleted`. L'admin può forzare manualmente l'invalidazione con il pulsante "Aggiorna ora" (`POST /dashboard/{user}/refresh`). L'admin può inoltre consultare le statistiche di qualsiasi utente via `GET /admin/users/{user}/stats` (protezione: `canEditUser()` o `isAdmin()`).
