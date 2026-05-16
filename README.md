# ScuolaGUIDA — Quiz App

Applicazione web per la gestione di quiz della patente di guida. Gli amministratori creano domande, le raggruppano in quiz e gestiscono l'intero ciclo di vita (bozza → pubblicato → confermato); gli utenti si registrano con email/password, completano la propria scheda anagrafica e — una volta approvati dall'amministratore — richiedono l'iscrizione ai quiz ufficiali, li svolgono e consultano le proprie statistiche.

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

### 6. Avvia il server di sviluppo

```bash
# Terminale 1 — asset Vite (hot reload)
npm run dev

# Terminale 2 — server PHP
php artisan serve
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
- **Storico tentativi** — rivedi tutti i tuoi quiz svolti
- **Ricerca** — cerca domande per testo o categoria

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
| **Service** | Tutta la business logic (9 service: Quiz, QuizAttempt, QuizEnrollment, Question, User, UserStats, DashboardStats, RolePermission, Search). |
| **Trait Auditable** | Logging automatico di ogni create/update/delete su tutti i modelli che lo usano. |
| **Observer** | Effetti collaterali post-salvataggio (invalidazione cache, ecc.) tenuti fuori dal service. |

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
