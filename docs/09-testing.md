# Test — copertura

La suite copre le funzionalità principali con Feature test (Laravel TestCase + `RefreshDatabase`). I componenti Livewire sono testati tramite `Livewire::test()` aggiunto al file Feature pertinente, non in classi dedicate.

```bash
# Tutta la suite
php artisan test

# Singolo file
php artisan test tests/Feature/NotificationsTest.php

# Singolo metodo
php artisan test --filter test_viewer_can_submit_anagrafica
```

---

## Convenzioni

- **Cartella unica** — `tests/Feature/`. Aggiungere test ai file esistenti prima di crearne di nuovi.
- **`RefreshDatabase`** in tutti i test che toccano il database.
- **Factories** per generare dati di test (non insert manuali).
- **`withoutMiddleware(EnsureTwoFactorAuthenticated::class)`** nel `setUp()` per i test admin che non devono verificare il 2FA stesso.
- **`Notification::fake()`** prima di asserire `assertSentTo` / `assertNotSentTo`.
- **`Storage::fake('public')`** quando si caricano file (documento identità, immagini domande, PDF materiali).

---

## Mappa completa

| File | Test | Aree coperte |
|---|---|---|
| `DiagnosticFeatureTest` | 17 | Accesso route (auth, 403 admin/editor), `generateQuestions` (una per categoria, no duplicati, salta categoria senza domande, esclude viste nelle ultime 24h), `saveResults` (persistenza, batch_id univoco, noop su array vuoto), `hasDiagnostic`, `getLatestDiagnostic` (batch più recente) |
| `SpacedRepetitionTest` | 12 | Algoritmo SM-2: prima/seconda/terza risposta corretta, reset su sbagliata, floor ease 1.30, ceiling ease 2.80, cap intervallo 365gg; integrazione: learned exclusion, upcoming count, studio crea review, quiz attempt crea review per ogni domanda |
| `StudyPlanFeatureTest` | 10 | Ordinamento mastery ascendente, solo diagnostico, solo storico, senza dati (empty state + mastery 0), `recommended_action` per i tre livelli, cascata delete utente → `diagnostic_results` |
| `CategoryMaterialTest` | 13 | Creazione di ogni tipo (note, link, PDF con upload), accesso negato a viewer, cascade delete categoria→materiali, eliminazione file fisico PDF, visibilità materiali nella pagina studio viewer, validazione file non-PDF e URL non valido, accessor YouTube embed, reorder |
| `ReviewErrorsTest` | 16 | Accesso (unauthenticated → login, admin → 403, viewer → 200), isolamento dati (viewer vede solo i propri errori), domanda corretta non appare, domanda sbagliata appare, attempt con `answers=[]` non conta, attempt con `answers=null` non conta, filtro categoria, limite `last_attempts`, markAsLearned esclude la domanda, unmarkAsLearned la reinserisce, idempotenza mark, personalità del toggle, cascade delete su utente, toggle `show_learned=1` |
| `TwoFactorTest` | 20 | Viewer senza sezione 2FA, admin/editor vedono la sezione, admin/editor senza 2FA → redirect setup, admin con 2FA senza sessione → redirect challenge, viewer bypassa il middleware, admin con sessione verificata → accesso, pagina setup accessibile, OTP valido abilita il 2FA, OTP non valido non abilita, challenge OTP valido/non valido, codice emergenza valido/consumato/già usato/non valido, disable con password corretta/errata, logout azzera `2fa_verified` |
| `MitImportTest` | 23 | Import valido con persistenza DB, deduplicazione `mit_code` (skip/update), argomento non mappato, testo vuoto, normalizzazione risposta vera/falsa (10 data provider), dry-run rollback, filtro `--topic`, POST HTTP + flash, validazione file (dimensione, assenza), viewer 403, invariante righe totali, fix `ImportQuestionsRequest max:5120` |
| `SimulatorTest` | 13 | Accesso autenticato/anonimo, start con/senza pool, play con/senza sessione attiva, autosave con score ricalcolato + protezione cross-user, submit + redirect risultato, destroy sessione, result owner/foreign-user, log warning su categoria mancante, `withDefault` su `QuizAttempt::quiz` |
| `QuestionReportTest` | 13 | Invio Livewire valido + persistenza DB, validazione `body` (min 10) e `type` (enum), anti-spam 3 pending, index admin 200/403, accept/reject con `resolved_by`/`resolved_at`/`admin_note`, destroy, KPI `$stats` corretti, cascade delete su `Question`, view show senza form di gestione per report risolti, editor con `edit_question` può moderare |
| `CalendarTest` | 16 | Accesso autenticato/anonimo, quiz nelle sezioni corrette (upcoming/open/closed/senza date), badge "Già iscritto", visibilità pulsante iscrizione, accessor `enrollment_status`, widget dashboard |
| `BookmarkTest` | 15 | Toggle add/remove, unique constraint, isolamento dati tra utenti, destroy 200/403, studio da bookmarks, redirect warning su lista vuota, cascade delete, accesso/redirect unauthenticated, filtri categoria e testo, saveNote Livewire (verifica pivot), validazione max 500 caratteri |
| `OfflineApiTest` | 18 | Auth viewer-only su `/api/offline/questions` e `/sync-answers`, throttle (200 → 429), validazione question_id, mock `SpacedRepetitionService` (per ogni risposta) e `StreakService` (una volta per sync), scritture in `question_reviews` e `user_activity_log`, `synced_ids` nel body, accessibilità pubblica di `/offline` |
| `GamificationTest` | 23 | `recordActivity` crea/incrementa il log giornaliero, `getCurrentStreak` con gap e senza attività oggi, `awardIfEligible` idempotenza + notifica, `checkAllBadges` per tutti i tipi di badge (streak, questions, first_pass, all_categories), widget streak dashboard, pagina badge accessibile/bloccata per ruolo |
| `QuizTest` | 3 | Creazione tentativo, tentativo su quiz confermato con iscrizione, aggiornamento score; accessori `getAnsweredAt`/`getTimeSpent`/`getAnswerPosition`; idempotenza migration `up()`/`down()` |
| `AdminOperativityTest` | 8 | Export Excel, riepilogo KPI, schedulazione iscrizioni, comando `close-expired` |
| `NotificationsTest` | 22 | Dispatch 11 notifiche, fallback fire-and-forget, payload `toDatabase()`, pagina `/notifications` (index/destroy/destroyAll + 403 cross-user), bell Livewire (unreadCount, markAllAsRead, markAsRead singola + redirect, markAsRead cross-user ignorata) |
| `GdprTest` | 9 | PII anonimizzata, blocco admin, dry-run, login impossibile, sessioni DB, `gdpr:list`, `--anonymized` filter |
| `RegistrationFlowTest` | 9 | Workflow iscrizione anagrafica end-to-end |
| `StudyTest` | 10 | Sessione studio, sorgenti, navigazione, flag, riepilogo |
| `UserStatsTest` | 9 | Dashboard, aggregati, cache, invalidazione, vista admin |
| `MediaManagerTest` | 8 | Render, switch folder, upload, duplicati, rename, delete |
| `AuditLogTest` | 6 | Creazione log su CRUD, accesso admin-only |
| `CategoryTest` | 4 | CRUD, permessi |
| `QuestionTest` | vari | CRUD domande |
| `ProfileTest` | 4 | Profilo, aggiornamento, cancellazione account |
| `Auth/*` | 13 | Login, logout, registrazione, reset password, verifica email |

**Totale stimato**: ~290 test in ~24 classi Feature.

---

## Pattern ricorrenti

### Test di componente Livewire

```php
use Livewire\Livewire;

Livewire::actingAs($viewer)
    ->test(\App\Http\Livewire\BookmarkButton::class, ['questionId' => $q->id])
    ->call('toggle')
    ->assertDispatched('bookmark-updated');
```

### Test di notifica fire-and-forget

```php
Notification::fake();

$this->actingAs($admin)
    ->post(route('admin.users.update', $viewer), [...])
    ->assertRedirect();

Notification::assertSentTo($viewer, RuoloAggiornatoNotification::class);
```

### Test con file upload

```php
Storage::fake('public');

$this->actingAs($viewer)
    ->post(route('viewer.registration.submit'), [
        'id_document' => UploadedFile::fake()->create('id.pdf', 1024, 'application/pdf'),
        // ...
    ]);

Storage::disk('public')->assertExists('registrations/...');
```

### Bypass del middleware 2FA negli altri Feature test

```php
protected function setUp(): void
{
    parent::setUp();
    $this->withoutMiddleware(\App\Http\Middleware\EnsureTwoFactorAuthenticated::class);
}
```

Usato dai 9 file di test admin esistenti (`AuditLogTest`, `QuestionTest`, `AdminOperativityTest`, `QuestionReportTest`, `CategoryTest`, `MitImportTest`, `NotificationsTest`, `RegistrationFlowTest`, `UserStatsTest`) per isolarli dal middleware aggiunto al gruppo admin con Feature 4.3.

---

## Checklist di test per nuova feature

Da rispettare prima di marcare una PR come pronta (estratto dalla checklist in `CLAUDE.md`):

- [ ] Almeno un Feature test per la funzionalità introdotta
- [ ] `php artisan test` — intera suite verde
- [ ] `RefreshDatabase` usato dove tocca il DB
- [ ] Test del componente Livewire se introdotto
- [ ] Test cascade delete se ci sono FK verso `users`
- [ ] Test di autorizzazione (`abort_unless` → 403)
