# Caching Review Report — 2026-05-29

---

## Executive Summary

Il progetto ha cinque release alle spalle, 22 service layer, 5 componenti Livewire e un approccio al
caching già avviato in alcune aree (`UserStatsService`, permessi per ruolo, lista categorie, badge
sidebar). L'analisi rivela tuttavia **sei aree critiche** che — se lasciate invariate — diventano
colli di bottiglia non appena il numero di utenti attivi contemporanei supera le 50-100 unità.

Il problema strutturale più grave è il **cache driver `database`**: ogni `Cache::remember` e ogni
`Cache::forget` emette una query SQL sulla tabella `cache`, il che significa che il caching attuale
serve più a ridurre la frequenza delle query sui dati che a eliminare il round-trip al DB.
Tutto il resto — compresi gli interventi di TTL e invalidation descritti in questo report — assume
un driver in-memory (Redis) per avere il ROI atteso.

---

## Carico query medio per pagina principale

| Pagina | Query stimate (stato attuale) | Query target (dopo interventi) |
|---|---|---|
| Dashboard viewer | 19–25 | 6–8 |
| Dashboard admin/editor | 9–10 | 3–4 |
| Studio (play) | 9–10 | 5–6 |
| Simulatore (play) | 8–9 | 5–6 |
| Revisione errori | 13–15 | 5–6 |
| Ripasso intelligente (SmartReview) | 9–11 | 4–5 |
| Calendario sessioni | 6–7 | 4–5 |
| Lista utenti (admin) | 4–5 | 3–4 |

> Il conteggio esclude le query di sessione/CSRF e le query di autenticazione. Include il layout
> (`admin_badges`, `SmartReview badge`, `NotificationBell`).

---

## Top 5 interventi per ROI

1. **Passaggio a Redis come cache driver** — elimina il round-trip SQL su ogni cache hit; prerequisito per tutti gli altri interventi.
2. **Cache `getUpcomingCount()` nel View::composer `layouts.admin`** — salva 4 query per ogni caricamento di pagina di ogni viewer.
3. **Cache `DashboardStatsService::kpi()` e `dailyCreated()`** — salva 6 query su ogni caricamento dashboard admin/editor.
4. **Cache count `ReviewErrorsService` nella dashboard** — evita di caricare 20 QuizAttempt con JSON solo per restituire un count.
5. **Rimozione `Question::$with = ['category']`** — elimina un JOIN implicito su ogni query `Question::` dove la categoria non serve.

---

## 1. Query globali (always-on)

### 1.1 `View::composer('*', ...)` — admin_badges

**File:** `app/Providers/AppServiceProvider.php:142`

Il composer gira su **ogni request** per ogni utente, inclusi viewer che vedono pagine di studio
dove il menu AdminLTE non esiste. Esegue un `Cache::remember('admin_badges', 60, ...)` che —
con driver `database` — emette comunque una SELECT sulla tabella `cache` ad ogni richiesta.

| Query nella callback (cache cold) | Tabella | Tipo |
|---|---|---|
| `User::where('created_at', '>=', $since)->count()` | `users` | COUNT |
| `Question::where('created_at', '>=', $since)->count()` | `questions` | COUNT |
| `Category::where('created_at', '>=', $since)->count()` | `categories` | COUNT |
| `Quiz::where('created_at', '>=', $since)->count()` | `quizzes` | COUNT |
| `AuditLog::where('created_at', '>=', $since)->count()` | `audit_logs` | COUNT |
| `User::where(role+reg_status+submitted_at)->count()` | `users` | COUNT |
| `QuestionReport::pending()->count()` | `question_reports` | COUNT |

- **Frequenza:** ogni request (cache cold = ogni 60s, cache warm = 1 SELECT su `cache`)
- **Rate cambio dati:** basso (i badge mostrano attività dell'ultima ora)
- **Problema principale:** con driver `database`, la cache warm richiede comunque 1 query su `cache`; in produzione con 100 utenti simultanei = 100 SELECT/s sulla tabella `cache`
- **Problema secondario:** il composer modifica `config('adminlte.menu')` anche per viewer su pagine senza il menu AdminLTE — lavoro inutile

### 1.2 `View::composer('layouts.admin', ...)` — SmartReview badge

**File:** `app/Providers/AppServiceProvider.php:121`

Gira su ogni pagina che usa `layouts.admin`, ossia tutte le pagine autenticate.
Per i viewer, chiama `SpacedRepetitionService::getUpcomingCount()` che emette **4 query** ad ogni
render, **senza alcuna cache**.

| Query | Tabella |
|---|---|
| `LearnedQuestion::where('user_id')->pluck('question_id')` | `learned_questions` |
| `COUNT ... where next_review_at <= endOfDay()` | `question_reviews` |
| `COUNT ... between startOfDay+1d and endOfDay+1d` | `question_reviews` |
| `COUNT ... where next_review_at <= endOfWeek()` | `question_reviews` |

- **Frequenza:** ogni page load per ogni viewer
- **Rate cambio dati:** basso (cambia solo dopo sessioni di studio o ripasso)
- **Costo stimato:** 4 query × ogni caricamento pagina viewer; con 50 utenti × 10 pagine/ora = 2.000 query/ora solo per questo badge
- **Inefficienza aggiuntiva:** il composer chiama `getUpcomingCount()` che restituisce `{due_today, due_tomorrow, due_this_week}`, ma usa solo `['due_today']` — 2 query su 4 sono completamente sprecate

### 1.3 `NotificationBell` — `wire:poll.30s`

**File:** `app/Http/Livewire/NotificationBell.php`, view: `resources/views/livewire/notification-bell.blade.php:2`

Il componente è incluso in `layouts.admin` (topbar) e aggiorna automaticamente ogni 30 secondi.

| Evento | Query emesse |
|---|---|
| mount (caricamento pagina) | `unreadNotifications()->count()` + `notifications()->limit(10)->get()` = 2 query |
| ogni poll (30s) | stesse 2 query (`loadNotifications()` + `render()`) |

- **Frequenza:** 2 query ogni 30 secondi per tab aperta
- **Rate cambio dati:** medio (notifiche arrivano in burst dopo azioni)
- **Costo aggregato stimato:** con 20 viewer con tab aperta = 40 query/30s = 80 query/min solo per le notifiche
- **Nota:** non c'è caching; `$user->unreadNotifications()->count()` è una query sempre live

### 1.4 Middleware e ServiceProvider

**File:** `bootstrap/app.php:15`

Solo due middleware custom (`RoleMiddleware`, `EnsureTwoFactorAuthenticated`). Nessuna query
globale extra rilevata. Il `RoleMiddleware` usa `hasPermission()` che chiama `User::rolePermissions()`
— già cached a 60s con `Cache::remember("role_perms_{$role}", 60, ...)`. Nessun problema.

**`AppServiceProvider::boot()`:** solo registrazione Observer e definizione Gate — nessuna query.

---

## 2. Service candidati a cache

### 2.1 `SpacedRepetitionService::getUpcomingCount()`

**File:** `app/Services/SpacedRepetitionService.php:97`

| Campo | Valore |
|---|---|
| Chiamato da | `AppServiceProvider.php:126` (ogni page load viewer) + `UserStatsController::me():58` |
| TTL suggerito | 300s (5 minuti) |
| Chiave cache | `sr_upcoming_{user_id}` |
| Invalidation trigger | `SpacedRepetitionService::recordAnswer()` → `Cache::forget("sr_upcoming_{user_id}")` |
| Risparmio | 4 query → 0 (cache warm) per ogni page load viewer |

**Nota:** il composer usa solo `['due_today']`; sarebbe sufficiente una query COUNT singola
(`where next_review_at <= now()->endOfDay()`), riducendo da 4 a 1 query anche in caso di cache miss.

### 2.2 `DashboardStatsService::kpi()`

**File:** `app/Services/DashboardStatsService.php:14`

| Campo | Valore |
|---|---|
| Chiamato da | `Admin\DashboardController::index()`, `UserStatsController::me()` |
| TTL suggerito | 300s |
| Chiave cache | `dashboard_kpi` |
| Invalidation trigger | Observer su User/Question/Category/Quiz (già esistono: `clearAdminBadgesCache()` — aggiungere `Cache::forget('dashboard_kpi')`) |
| Risparmio | 4 COUNT query → 0 per ogni dashboard load admin/editor |

### 2.3 `DashboardStatsService::dailyCreated()`

**File:** `app/Services/DashboardStatsService.php:26`

| Campo | Valore |
|---|---|
| Chiamato da | `Admin\DashboardController::index()` (2×: Questions + Users) |
| TTL suggerito | 900s (15 minuti) |
| Chiave cache | `daily_chart_{model_class}_{days}` |
| Invalidation trigger | time-based (dati storici, nessuna invalidation hard) |
| Risparmio | 2 aggregate query → 0 per ogni dashboard load |

### 2.4 `StreakService::getCurrentStreak()` e `getLongestStreak()`

**File:** `app/Services/StreakService.php:31` e `:65`

| Campo | Valore |
|---|---|
| Chiamato da | `UserStatsController::me():45-46` (ogni dashboard viewer) |
| TTL suggerito | fino a mezzanotte (`now()->endOfDay()->diffInSeconds(now())`) o 900s |
| Chiave cache | `streak_{user_id}` → array `{current, longest}` in un'unica entry |
| Invalidation trigger | `StreakService::recordActivity()` → `Cache::forget("streak_{user_id}")` |
| Risparmio | 3 query → 0 per ogni dashboard viewer |
| Note | Unire le due chiamate in un metodo `getStreakStats()` che ritorna entrambi i valori, evita due query separate |

### 2.5 `ReviewErrorsService::getErrors()` — count nella dashboard

**File:** `app/Services/ReviewErrorsService.php:12`

| Campo | Valore |
|---|---|
| Chiamato da | `UserStatsController::me():56` solo per `.count()` |
| Problema | Carica 20 QuizAttempt con colonna `answers` (JSON potenzialmente largo) solo per contare gli errori |
| Soluzione | Aggiungere `ReviewErrorsService::getErrorCount(User $user): int` con cache, oppure cacheare il risultato di `getErrors()` |
| TTL suggerito | 600s (10 minuti) |
| Chiave cache | `review_errors_count_{user_id}` |
| Invalidation trigger | dopo `QuizAttemptService::record()` → `Cache::forget("review_errors_count_{user_id}")` |
| Risparmio | 3-4 query + deserializzazione JSON → 0 per ogni dashboard load |

### 2.6 `StudyPlanService::buildPlan()`

**File:** `app/Services/StudyPlanService.php:27`

| Campo | Valore |
|---|---|
| Operazione | Carica TUTTE le categorie + TUTTE le domande (pluck) + TUTTI i `quiz_attempts.answers` (JSON) + ultimo batch diagnostico |
| Problema | `aggregateHistoricalStats()` carica tutti gli `answers` in memoria e li itera in PHP — O(N×M) dove N = tentativi, M = domande per tentativo |
| TTL suggerito | 900s |
| Chiave cache | `study_plan_{user_id}` |
| Invalidation trigger | dopo `QuizAttemptService::record()` + dopo `DiagnosticService::saveResults()` |
| Risparmio | Importante per utenti con molti tentativi (>100 quiz completati) |

### 2.7 `BadgeService::checkAllBadges()`

**File:** `app/Services/BadgeService.php:38`

| Campo | Valore |
|---|---|
| Chiamato da | `QuizAttemptService::record():71` (ogni quiz completato), `StudyController::flag():112` (ogni risposta durante studio), `SimulatorController::submit():140` (ogni simulazione) |
| Query emesse (worst case) | 1 (earned badges pluck) + 1 (streak) + 1 (totalAnswered SUM) + 1 (first_pass query) + 1 (all_categories join) = 5 query |
| Fast path attuale | Se badge già guadagnato: `isset($earned[$code])` skippa la query — buono per utenti avanzati |
| Problema | Per utenti nuovi: tutte e 5 le query vengono eseguite ad ogni risposta durante lo studio |
| Soluzione | Cacheare il set dei badge già guadagnati: `Cache::remember("earned_badges_{user_id}", 1800, ...)` invalidato da `awardIfEligible()` |
| Risparmio | Fino a 4 query risparmiate per ogni risposta durante lo studio (hot path) |

### 2.8 `UserStatsService` _(già implementata — analisi stato attuale)_

**File:** `app/Services/UserStatsService.php:26`

TTL 600s, chiave `user_stats_{user_id}`, invalidation via `UserStatsService::forget()`. Implementazione
corretta. Unica nota: `forget()` viene chiamato da `UserStatsController::refresh()` — verificare che
venga invocato anche dopo `QuizAttemptService::record()` (attualmente non lo è).

### 2.9 `User::rolePermissions()` _(già implementata)_

**File:** `app/Models/User.php:294`

TTL 60s — accettabile. Invalidation in `RolePermissionService::syncMatrix()` e `RolePermission` model.
Nessuna azione richiesta.

### 2.10 `categories_list` in `QuestionController::index()` _(già implementata)_

**File:** `app/Http/Controllers/QuestionController.php:39`

TTL 3600s, invalidation via `CategoryObserver`. Implementazione corretta. Nessuna azione richiesta.

---

## 3. Query N+1 residue

### 3.1 `SimulatorService::buildQuestionList()` — N+1 su Category

**File:** `app/Services/SimulatorService.php:26`

```php
// PROBLEMA: un Category::whereRaw() per ogni voce in config('simulator.distribution')
foreach ($distribution as $categoryName => $count) {
    $category = Category::whereRaw('LOWER(name) LIKE ?', [...]) ->first(); // query N
    $extracted = Question::where('category_id', $category->id)              // query N
        ->inRandomOrder()->limit($count)->get();
}
```

Con 10 categorie nella distribuzione: **20 query** (10 category lookup + 10 question fetch).

**Fix proposto:** pre-caricare tutte le categorie con una query e mappare per nome in memoria.

```php
// Target: 1 query Category + 10 query Question (per le distribuzioni)
$allCategories = Category::get()->keyBy(fn ($c) => strtolower($c->name));
foreach ($distribution as $categoryName => $count) {
    $category = $allCategories->first(fn ($c, $k) => str_contains($k, strtolower($categoryName)));
    // ...
}
```

- **Query attuali:** 20+
- **Query target:** 11 (1 Category + 1 per distribuzione)
- **Impatto:** ogni avvio simulatore

### 3.2 `StudyController::play()` — Question::find() per singola domanda

**File:** `app/Services/StudyService.php:127`

```php
public function currentQuestion(): ?Question
{
    return $id ? Question::find($id) : null; // 2 query: questions + categories (via $with)
}
```

Ogni navigazione durante lo studio emette 2 query. Non è un N+1 classico (è user-driven, non in loop),
ma `Question::$with = ['category']` forza sempre il JOIN della categoria anche quando la pagina
`study.play` già carica i materiali della categoria separatamente (`$question->category->load(...)`).

- **Query attuali:** 2 per navigazione
- **Query target:** 1 + 1 load esplicita dove serve (rimuovendo `$with`)

### 3.3 `ReviewErrorsController::index()` — doppia query `learned_questions`

**File:** `app/Http/Controllers/ReviewErrorsController.php:43`

```php
$errors       = $this->service->getErrors($user, $categoryId, $lastAttempts); // carica learnedIds internamente
$learnedCount = $this->service->getLearned($user)->count();                    // ri-carica learnedIds!
```

`getErrors()` a riga `app/Services/ReviewErrorsService.php:49` fa `LearnedQuestion::where('user_id')->pluck('question_id')`.
`getLearned()` a riga `app/Services/ReviewErrorsService.php:103` fa la stessa query.

**Fix:** `getErrors()` dovrebbe ritornare anche il count degli imparati, oppure il controller
dovrebbe passare i `learnedIds` già caricati tra le due chiamate.

- **Query duplicate:** 1 `learned_questions` pluck per ogni visita alla pagina

### 3.4 `UserStatsController::me()` — `getUpcomingCount()` chiamato due volte

**File:** `app/Http/Controllers/UserStatsController.php:58` e `app/Providers/AppServiceProvider.php:126`

La dashboard viewer chiama `SpacedRepetitionService::getUpcomingCount()` direttamente (riga 58) **e**
la stessa funzione viene chiamata dal `View::composer('layouts.admin', ...)` (riga 126 di `AppServiceProvider`).
Risultato: **8 query** per `question_reviews` + `learned_questions` per la sola dashboard, che potrebbero
essere 0 con la cache proposta in §2.1.

### 3.5 `DiagnosticService::generateQuestions()` — N+1 su Category

**File:** `app/Services/DiagnosticService.php:22`

```php
$categories = Category::whereHas('questions')->get(); // 1 query

foreach ($categories as $category) {
    $question = $category->questions()->inRandomOrder()->... ->first(); // N query (una per categoria)
}
```

Con N categorie: N+1 query. Per il test diagnostico (una query per categoria) è accettabile in
quanto chiamato raramente (avvio test, non ogni page load). Segnalato per completezza.

---

## 4. Conteggi always-on — analisi aggregata

Costo per caricamento di una pagina qualsiasi con layout admin, utente viewer:

| Componente | Query (cache cold) | Query (cache warm) | Frequenza |
|---|---|---|---|
| `admin_badges` View::composer | 7 + 1 write | 1 SELECT su `cache` table | ogni request |
| `SmartReview badge` View::composer | 4 | **0 (non c'è cache!)** | ogni request viewer |
| `NotificationBell::mount()` | 2 | 2 (sempre live) | ogni page load |
| **Totale layout (viewer)** | **13** | **3+** | ogni request |

Il dato più critico: il `SmartReview badge` non ha **nessuna cache**, emette sempre 4 query.
Con 50 viewer che caricano 10 pagine/ora ciascuno: **2.000 query/ora** inutili — dati che
cambiano solo dopo sessioni di studio, non tra una navigazione e l'altra.

### `NotificationBell` — costo polling aggregato

Con 30 viewer attivi (tab aperta):
- 2 query × ogni 30s × 30 utenti = **120 query/minuto** solo per il poll notifiche
- La maggior parte delle volte restituisce lo stesso `unreadCount` dell'ultimo poll

**Proposta:** aggiungere `wire:poll.30s` condizionale solo se ci sono notifiche non lette, oppure
usare `Cache::remember("notif_unread_{user_id}", 30, ...)` dentro `loadNotifications()`.

---

## 5. Infrastruttura cache — stato attuale e raccomandazioni

### 5.1 Driver attuale: `database`

**`.env`:** `CACHE_STORE=database`  
**`config/cache.php:18`:** `'default' => env('CACHE_STORE', 'database')`

Con driver `database`:
- Ogni `Cache::remember(key, ttl, fn)` → `SELECT value FROM cache WHERE key = ?`
- Cache miss → `INSERT INTO cache ...` (o UPDATE se key esiste scaduta)
- Ogni `Cache::forget(key)` → `DELETE FROM cache WHERE key = ?`

Il caching attuale è **parzialmente vanificato**: una cache hit riduce 7 query applicative a 1,
ma quella 1 rimane pur sempre un round-trip al DB MySQL.

### 5.2 Redis già configurato (non usato per cache)

**`.env`** contiene già:
```
REDIS_CLIENT=phpredis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
```

Redis è configurato per sessioni/queue ma non per cache. Il passaggio richiede una sola variabile.

### 5.3 Passi per migrare a Redis

**Step 1 — .env (solo dev/staging):**
```
CACHE_STORE=redis
REDIS_CACHE_CONNECTION=cache
```

**Step 2 — `config/database.php` (Redis connections, già presente in Laravel 11 default):**
Verificare che esista la connection `cache` sotto `redis`:
```php
'cache' => [
    'url'      => env('REDIS_URL'),
    'host'     => env('REDIS_HOST', '127.0.0.1'),
    'password' => env('REDIS_PASSWORD'),
    'port'     => env('REDIS_PORT', 6379),
    'database' => env('REDIS_CACHE_DB', '1'), // db separato da sessioni/queue
],
```

**Step 3 — fallback per ambienti senza Redis (es. CI/CD, Valet locale senza Redis):**
```
CACHE_STORE=file  # fallback sicuro
```

**Step 4 — verificare `php artisan cache:table` non sia rimasta come unica entry di migration:**
La tabella `cache` può essere mantenuta per compatibilità, ma non sarà più usata.

**Step 5 — produzione:** aggiungere `CACHE_STORE=redis` su Forge/Ploi/Serverpilot + supervisore Redis già attivo.

### 5.4 Coerenza tra le cache esistenti

| Chiave | TTL | Driver usato | Invalidation |
|---|---|---|---|
| `admin_badges` | 60s | database | clearAdminBadgesCache() via Observer |
| `user_stats_{id}` | 600s | database | UserStatsService::forget() |
| `role_perms_{role}` | 60s | database | RolePermissionService::syncMatrix() + RolePermission model |
| `categories_list` | 3600s | database | CategoryObserver::saved/deleted |

Tutte le implementazioni esistenti sono **coerenti** tra loro: usano `Cache::remember` + `Cache::forget`
con chiave nominale + TTL esplicito. Il pattern è corretto; va solo replicato nelle aree mancanti.

**Anomalia rilevata:** `UserStatsService::forget()` non viene chiamato da `QuizAttemptService::record()`.
I dati di `user_stats` cambiano ad ogni tentativo completato, ma la cache si aggiorna solo dopo il
TTL di 10 minuti o tramite il pulsante "Aggiorna statistiche". Va valutato se invalidare anche
post-`QuizAttemptService::record()`.

---

## 6. Eloquent specifici

### 6.1 `Question::$with = ['category']` — caricamento implicito ovunque

**File:** `app/Models/Question.php:24`

```php
protected $with = ['category']; // carica sempre category automaticamente
```

`$with` globale significa che **ogni** `Question::find()`, `Question::whereIn()`, `Question::inRandomOrder()`,
`QuestionReview::with(['question'])` emette automaticamente una seconda query per le categorie,
anche quando la categoria non è necessaria.

| Contesto | Categoria necessaria? | Overhead |
|---|---|---|
| `QuizAttemptService::record()` — pluck `is_true` | No | 1 query in più per batch |
| `SimulatorService::buildQuestionList()` | No (nella build list) | N query in più |
| `SimulatorService::loadSessionQuestions()` — JSON payload per la view | Sì (image path) | OK |
| `SpacedRepetitionService::getDueQuestions()` | Sì (`with(['question.category'])`) | Già eager, ok |
| `StudyService::currentQuestion()` → `Question::find()` | Sì, ma viene ricaricata sotto (`$question->category->load(...)`) | Doppio load |
| `ReviewErrorsService::getErrors()` → `Question::whereIn()` | Sì | OK |
| `BadgeService::checkAllBadges()` — nessuna Question query diretta | n/a | — |

**Fix:** rimuovere `$with = ['category']` da `Question` e aggiungere `->with('category')` esplicito
solo dove è effettivamente consumata.

### 6.2 `DashboardStatsService` — accessor aggregati senza cache

**File:** `app/Services/DashboardStatsService.php`

`kpi()` usa 4 `Model::count()` separati invece di una singola query aggregata su più tabelle.
Meno critico dell'assenza di cache, ma se si aggiunge il caching (§2.2), il fix diventa inutile.

### 6.3 `StudyPlanService::aggregateHistoricalStats()` — scan JSON in PHP

**File:** `app/Services/StudyPlanService.php:73`

```php
$attempts = QuizAttempt::where('user_id', $user->id)->select('answers')->get(); // N righe JSON

foreach ($attempts as $attempt) {
    foreach ($attempt->answers ?? [] as $questionId => $answer) { // M answers per tentativo
        // ...
    }
}
```

Carica tutti i tentativi con il campo `answers` (JSON pesante) e li itera in PHP.
Per un viewer con 200 tentativi da 30 domande: 6.000 iterazioni + deserializzazione di 200 JSON blob.

**Alternativa futura (denormalizzazione):** una tabella `quiz_attempt_stats` con aggregati
per categoria (totale/corretti) aggiornata incrementalmente da un Observer su `QuizAttempt`.
Nel breve termine: cache in §2.6 maschera il problema; nel lungo termine è la soluzione corretta.

### 6.4 `UserActivityLog` — query duplicata nella dashboard viewer

**File:** `app/Http/Controllers/UserStatsController.php:47`

```php
$activityToday = UserActivityLog::where('user_id', $user->id)
    ->where('activity_date', Carbon::today()->toDateString())
    ->exists();
```

`StreakService::getCurrentStreak()` (chiamato 3 righe sopra) già legge la stessa tabella
con una query molto simile. In un refactor futuro, `getCurrentStreak()` potrebbe restituire
anche `hasActivityToday` per evitare la query extra.

---

## 7. Piano di intervento proposto

Le PR sono ordinate per ROI decrescente (beneficio / sforzo). Ogni PR è atomica e indipendente
dalle successive, salvo ove indicato esplicitamente.

---

### PR-C1 — Migrazione cache driver da `database` a `redis`

**Effort:** S | **ROI:** Massimo — prerequisito per tutto il resto

**Cosa cambia:**
- `.env` (dev + prod): `CACHE_STORE=redis`
- `config/database.php`: verifica connection `cache` con `database: 1`
- Nessuna modifica al codice applicativo

**Rischi:** Redis non disponibile in alcuni ambienti locali → fallback a `file` in `.env.example`

**Test:** `php artisan cache:clear && php artisan test` — nessun test va modificato

---

### PR-C2 — Cache `SpacedRepetitionService::getUpcomingCount()` + ottimizzazione View::composer

**Effort:** S | **ROI:** Alto (salva 4 query per ogni page load viewer)

**File da modificare:**
- `app/Services/SpacedRepetitionService.php:97` — aggiungere `Cache::remember("sr_upcoming_{$user->id}", 300, ...)`
- `app/Providers/AppServiceProvider.php:126` — usare il risultato cached
- `app/Services/SpacedRepetitionService.php:17` — `recordAnswer()`: aggiungere `Cache::forget("sr_upcoming_{$user->id}")`
- Bonus: far restituire solo `due_today` dal composer (o usare metodo separato `getDueTodayCount()`)

**Chiavi cache:**
```
sr_upcoming_{user_id}  →  array{due_today: int, due_tomorrow: int, due_this_week: int}
```

**Invalidation:** `SpacedRepetitionService::recordAnswer()` + `ReviewErrorsService::markAsLearned/unmarkAsLearned`

---

### PR-C3 — Cache `DashboardStatsService`

**Effort:** S | **ROI:** Alto (salva 6 query per ogni dashboard admin/editor)

**File da modificare:**
- `app/Services/DashboardStatsService.php:14` — `kpi()`: `Cache::remember('dashboard_kpi', 300, ...)`
- `app/Services/DashboardStatsService.php:26` — `dailyCreated()`: `Cache::remember("daily_chart_{$model}_{$days}", 900, ...)`
- Observers `UserObserver`, `QuestionObserver`, `CategoryObserver`, `QuizObserver` — aggiungere `Cache::forget('dashboard_kpi')`

**Chiavi cache:**
```
dashboard_kpi          → array{users, questions, categories, quizzes}
daily_chart_{Model}_{days} → Collection
```

---

### PR-C4 — Cache `StreakService` + eliminazione query duplicata `UserActivityLog`

**Effort:** S | **ROI:** Medio-alto (salva 3-4 query per ogni dashboard viewer)

**File da modificare:**
- `app/Services/StreakService.php` — aggiungere metodo `getStats(User $user): array` che ritorna `{current, longest, has_today}` in un'unica voce cached
- `app/Services/StreakService.php:13` — `recordActivity()`: `Cache::forget("streak_{$user->id}")`
- `app/Http/Controllers/UserStatsController.php:44` — usare `StreakService::getStats()` invece di 3 chiamate separate

**Chiave cache:**
```
streak_{user_id}  →  array{current: int, longest: int, has_today: bool}
```

---

### PR-C5 — Cache count `ReviewErrorsService` nella dashboard

**Effort:** S | **ROI:** Medio (salva 3-4 query pesanti per ogni dashboard viewer)

**File da modificare:**
- `app/Services/ReviewErrorsService.php` — aggiungere `getErrorCount(User $user, ?int $categoryId = null): int` con `Cache::remember("review_errors_count_{$user->id}", 600, ...)`
- `app/Http/Controllers/UserStatsController.php:56` — usare `getErrorCount()` invece di `getErrors()->count()`
- `app/Services/QuizAttemptService.php:56` (post-record) — `Cache::forget("review_errors_count_{$user->id}")`

**Chiave cache:**
```
review_errors_count_{user_id}  →  int
```

---

### PR-C6 — Fix `SimulatorService::buildQuestionList()` N+1 su Category

**Effort:** S | **ROI:** Medio (ogni avvio simulatore: da 20+ query a ~11)

**File da modificare:** `app/Services/SimulatorService.php:22`

**Fix:** pre-caricare tutte le categorie con una query, fare lookup in memoria con `str_contains` sul nome lowercase.

---

### PR-C7 — Rimozione `Question::$with = ['category']`

**Effort:** M | **ROI:** Medio (elimina JOIN implicito in tutti i contesti dove non serve)

**Dipendenza:** richiede una ricognizione di tutte le chiamate `Question::` nel codebase per aggiungere `->with('category')` dove la categoria è effettivamente usata nella view/service.

**File da modificare:** `app/Models/Question.php:24` + aggiunte esplicite in ~8 punti del codebase.

**Rischio:** regressioni se qualche view usa `$question->category` senza eager load esplicito → test esistenti + Debugbar in dev rilevano i lazy load mancanti.

---

### PR-C8 — Cache `BadgeService::earned_badges` per fast-path

**Effort:** S | **ROI:** Medio (salva fino a 4 query per ogni risposta durante lo studio)

**File da modificare:**
- `app/Services/BadgeService.php:39` — `checkAllBadges()`: cacheare il pluck dei badge guadagnati
- `app/Services/BadgeService.php:27` — `awardIfEligible()`: `Cache::forget("earned_badges_{$user->id}")` dopo award

**Chiave cache:**
```
earned_badges_{user_id}  →  Collection (badge_code flipped)
```
**TTL:** 1800s (30 minuti); l'invalidation sull'award è il meccanismo primario.

---

### PR-C9 — Fix duplicazione `LearnedQuestion` in `ReviewErrorsController`

**Effort:** XS | **ROI:** Basso (salva 1 query per visita alla pagina revisione errori)

**File da modificare:** `app/Http/Controllers/ReviewErrorsController.php:43` e
`app/Services/ReviewErrorsService.php` — aggiungere parametro opzionale `learnedIds` o esporre il count direttamente.

---

### PR-C10 — `NotificationBell` — cache unread count breve TTL

**Effort:** XS | **ROI:** Basso-medio (riduce 80 query/min per polling notification a 0 query se nessuna notifica non letta)

**File da modificare:** `app/Http/Livewire/NotificationBell.php:21` — `loadNotifications()`:
```php
$this->unreadCount = Cache::remember("notif_unread_{$user->id}", 30, fn() =>
    $user->unreadNotifications()->count()
);
```
Invalidation: quando una notifica arriva (nel `NotificationService::send()` → `Cache::forget("notif_unread_{$user->id}")`)
e quando `markAsRead()` / `markAllAsRead()` vengono chiamati (già sono nel componente Livewire).

---

## Riepilogo PR per priorità

| PR | Effort | ROI | Query risparmiate/req | Dipendenze |
|---|---|---|---|---|
| PR-C1 | S | Massimo | N/A (overhead infrastrutturale) | — |
| PR-C2 | S | Alto | -4 per ogni page load viewer | PR-C1 raccomandato prima |
| PR-C3 | S | Alto | -6 per ogni dashboard admin | PR-C1 raccomandato prima |
| PR-C4 | S | Medio-alto | -3 per ogni dashboard viewer | PR-C1 |
| PR-C5 | S | Medio | -3 per ogni dashboard viewer | PR-C1 |
| PR-C6 | S | Medio | -10 per ogni avvio simulatore | — |
| PR-C7 | M | Medio | -1 per ogni Question query senza categoria | — |
| PR-C8 | S | Medio | -4 per ogni risposta studio | PR-C1 |
| PR-C9 | XS | Basso | -1 per ogni visita revisione errori | — |
| PR-C10 | XS | Basso-medio | -2/30s per viewer connessi | PR-C1 |

> **Sequenza consigliata:** PR-C1 → PR-C2 + PR-C3 (in parallelo) → PR-C4 + PR-C5 (in parallelo)
> → PR-C6 → PR-C7 → PR-C8 + PR-C9 + PR-C10 (in parallelo)
