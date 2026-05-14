# Scuola Guida — Quiz App

Applicazione web per la gestione di quiz della patente di guida. Permette agli admin di creare domande, raggrupparle in quiz e assegnarli agli utenti; gli utenti possono svolgere i quiz e consultare le proprie statistiche.

**Stack:** Laravel 11 · Blade · AdminLTE 3 · Bootstrap 5 · Livewire 3 · MySQL

---

## Installazione da zero

### Prerequisiti

| Tool | Versione minima |
|---|---|
| PHP | 8.2 |
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

Apri `.env` e imposta le credenziali del database:

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
php artisan migrate:fresh --seed
```

Il seeder crea:
- Un utente **admin** (`admin@test.com` / `password`)
- Categorie di esempio
- Domande campione

### 5. Storage pubblico

```bash
php artisan storage:link
```

Crea il symlink `public/storage → storage/app/public` necessario per le immagini delle domande.

### 6. Avvia il server di sviluppo

In due terminali separati (oppure con un process manager come [Herd](https://herd.laravel.com/)):

```bash
# Terminale 1 — asset Vite
npm run dev

# Terminale 2 — server PHP
php artisan serve
```

Apri [http://127.0.0.1:8000](http://127.0.0.1:8000) e accedi con `admin@test.com` / `password`, poi vai su `/admin`.

### Comandi utili

```bash
php artisan test                    # esegui la test suite
php artisan migrate:fresh --seed    # reset completo del DB
php artisan route:list              # elenco di tutte le route
```

---

## Business logic — flusso di una chiamata

Esempio: **aggiornamento di una domanda** (`PUT /admin/questions/{id}`).

Il flusso attraversa cinque strati in sequenza: **Route → Middleware → FormRequest → Controller → Service → Model/Observer**.

```
Browser
  │
  │  PUT /admin/questions/42
  ▼
┌─────────────────────────────────────────────────────┐
│  routes/web.php                                     │
│                                                     │
│  Route::middleware(['auth', 'role:admin,editor,     │
│    viewer'])->resource('questions', ...)            │
│                                                     │
│  → QuestionController@update                        │
└───────────────────────┬─────────────────────────────┘
                        │
                        ▼
┌─────────────────────────────────────────────────────┐
│  UpdateQuestionRequest (FormRequest)                │
│                                                     │
│  1. authorize()  → $user->canEditQuestion()         │
│     └ verifica il permesso 'edit_questions'         │
│        nel campo JSON permissions dell'utente       │
│     └ abort 403 se non autorizzato                  │
│                                                     │
│  2. prepareForValidation()                          │
│     └ normalizza is_true a boolean                  │
│                                                     │
│  3. rules() — valida:                               │
│     · category_id  required|exists:categories,id   │
│     · question     required|string                  │
│     · is_true      boolean                          │
│     · image        nullable|image|max:2048          │
└───────────────────────┬─────────────────────────────┘
                        │ $request->validated()
                        ▼
┌─────────────────────────────────────────────────────┐
│  QuestionController@update                          │
│                                                     │
│  public function update(                            │
│    UpdateQuestionRequest $request,                  │
│    Question $question        ← route model binding  │
│  ) {                                                │
│    $this->service->update(                          │
│      $question,                                     │
│      $request->validated(),                         │
│      $request->file('image')   ← separato: non     │
│    );                            passa da validated │
│    return redirect()->route('admin.questions.index')│
│      ->with('success', 'Domanda aggiornata');       │
│  }                                                  │
└───────────────────────┬─────────────────────────────┘
                        │
                        ▼
┌─────────────────────────────────────────────────────┐
│  QuestionService@update                             │
│                                                     │
│  1. Rimuove 'image' dall'array dati                 │
│     (il file viene gestito a parte)                 │
│                                                     │
│  2. Se arriva un nuovo file immagine:               │
│     a. deleteImage($question)                       │
│        └ cancella il vecchio file da               │
│          storage/app/public/questions/              │
│          (se non è un URL esterno)                  │
│     b. storeImage($file)                            │
│        └ salva il nuovo file e aggiunge             │
│          il path all'array dati                     │
│                                                     │
│  3. $question->update($data)                        │
│     └ Eloquent scrive sul DB                        │
└───────────────────────┬─────────────────────────────┘
                        │  evento 'updated' di Eloquent
                        ▼
┌─────────────────────────────────────────────────────┐
│  Trait Auditable (bootAuditable)                    │
│                                                     │
│  Intercetta l'evento updated e scrive su            │
│  audit_logs:                                        │
│  · user_id   → chi ha fatto la modifica             │
│  · event     → 'updated'                            │
│  · model_type→ 'App\Models\Question'                │
│  · model_id  → 42                                   │
│  · old_values→ campi prima della modifica           │
│  · new_values→ campi dopo la modifica               │
└───────────────────────┬─────────────────────────────┘
                        │  evento 'saved' di Eloquent
                        ▼
┌─────────────────────────────────────────────────────┐
│  QuestionObserver@saved                             │
│                                                     │
│  clearAdminBadgesCache()                            │
│  └ invalida la cache 'admin_badges' (TTL 60 s)     │
│    così i contatori in sidebar si aggiornano        │
│    alla prossima request                            │
└───────────────────────┬─────────────────────────────┘
                        │
                        ▼
                   redirect 302
               → admin.questions.index
               + flash 'success'
```

### Punti chiave dell'architettura

| Strato | Responsabilità |
|---|---|
| **FormRequest** | Autorizzazione + validazione. Il controller non vede mai dati non validati. |
| **Controller** | Orchestrazione pura: chiama il service, ritorna la risposta. Nessuna logica. |
| **Service** | Tutta la business logic: gestione file, aggiornamento del modello. |
| **Trait Auditable** | Logging automatico di ogni create/update/delete su tutti i modelli che lo usano. |
| **Observer** | Effetti collaterali post-salvataggio (invalidazione cache, notifiche, ecc.) tenuti fuori dal service. |

---

## Ruoli e permessi

| Ruolo | Accesso |
|---|---|
| `admin` | Tutto, inclusa dashboard, audit log, gestione ruoli |
| `editor` | CRUD su domande, categorie, quiz |
| `viewer` | Solo lettura |

I permessi granulari (`edit_questions`, `delete_questions`, …) sono configurabili per ruolo dalla pagina **Admin → Ruoli & Permessi** e sono salvati come JSON nel campo `permissions` della tabella `users`.
