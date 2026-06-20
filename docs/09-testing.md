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

## Indice

1. [Convenzioni](#convenzioni)
2. [Mappa completa](#mappa-completa)
3. [Pattern ricorrenti](#pattern-ricorrenti)
4. [Checklist di test per nuova feature](#checklist-di-test-per-nuova-feature)

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
| `InstructorTest` | 30 | Autorizzazione per ruolo (instructor/admin/viewer), idempotenza assign studenti, cascadeOnDelete pivot, `canEditXxx()` false per instructor, `getStudentProgress()` chiavi corrette, note CRUD (add/delete), cascade note su delete utente, export PDF progress, dispatch `InstructorStudentOutcome` al completamento quiz |
| `QuizTest` | 25 | Creazione tentativo, tentativo su quiz confermato con iscrizione, aggiornamento score; accessori `getAnsweredAt`/`getTimeSpent`/`getAnswerPosition`; idempotenza migration `up()`/`down()`; flussi quiz multi-patente e format esame |
| `GamificationTest` | 23 | `recordActivity` crea/incrementa il log giornaliero, `getCurrentStreak` con gap e senza attività oggi, `awardIfEligible` idempotenza + notifica, `checkAllBadges` per tutti i tipi di badge (streak, questions, first_pass, all_categories), widget streak dashboard, pagina badge accessibile/bloccata per ruolo |
| `NotificationsTest` | 22 | Dispatch 11 notifiche, fallback fire-and-forget, payload `toDatabase()`, pagina `/notifications` (index/destroy/destroyAll + 403 cross-user), bell Livewire (unreadCount, markAllAsRead, markAsRead singola + redirect, markAsRead cross-user ignorata) |
| `TwoFactorTest` | 20 | Viewer senza sezione 2FA, admin/editor vedono la sezione, admin/editor senza 2FA → redirect setup, admin con 2FA senza sessione → redirect challenge, viewer bypassa il middleware, admin con sessione verificata → accesso, pagina setup accessibile, OTP valido abilita il 2FA, OTP non valido non abilita, challenge OTP valido/non valido, codice emergenza valido/consumato/già usato/non valido, disable con password corretta/errata, logout azzera `2fa_verified` |
| `QuestionTranslationTest` | 18 | Accessor `getLocalizedText()` + fallback italiano, idempotenza upsert, autorizzazione admin/editor/viewer, localizzazione view studio, cascade delete, preferenza profilo viewer, edit page integrazione, validazione locale |
| `OfflineApiTest` | 18 | Auth viewer-only su `/api/offline/questions` e `/sync-answers`, throttle (200 → 429), validazione question_id, mock `SpacedRepetitionService` (per ogni risposta) e `StreakService` (una volta per sync), scritture in `question_reviews` e `user_activity_log`, `synced_ids` nel body, accessibilità pubblica di `/offline` |
| `StudyTest` | 17 | Sessione studio, sorgenti, navigazione, flag, riepilogo, filtro per tipo patente, domande bookmark |
| `DiagnosticFeatureTest` | 17 | Accesso route (auth, 403 admin/editor), `generateQuestions` (una per categoria, no duplicati, salta categoria senza domande, esclude viste nelle ultime 24h), `saveResults` (persistenza, batch_id univoco, noop su array vuoto), `hasDiagnostic`, `getLatestDiagnostic` (batch più recente) |
| `ReviewErrorsTest` | 16 | Accesso (unauthenticated → login, admin → 403, viewer → 200), isolamento dati (viewer vede solo i propri errori), domanda corretta non appare, domanda sbagliata appare, attempt con `answers=[]` non conta, attempt con `answers=null` non conta, filtro categoria, limite `last_attempts`, markAsLearned esclude la domanda, unmarkAsLearned la reinserisce, idempotenza mark, personalità del toggle, cascade delete su utente, toggle `show_learned=1` |
| `HealthTest` | 16 | Accesso admin/editor/viewer, `getDatabaseSize` coerente, `getQueueStatus` conta pending e failed, resilienza su disco mancante, `backup:check` exit code 1 senza backup, `BackupFailed` inviata agli admin all'evento, canali corretti, payload `toDatabase` con chiavi richieste, sanitizzazione path, `formatBytes` |
| `CalendarTest` | 16 | Accesso autenticato/anonimo, quiz nelle sezioni corrette (upcoming/open/closed/senza date), badge "Già iscritto", visibilità pulsante iscrizione, accessor `enrollment_status`, widget dashboard |
| `MitImportTest` | 15 | Import valido con persistenza DB, deduplicazione `mit_code` (skip/update), argomento non mappato, testo vuoto, dry-run rollback, filtro `--topic`, POST HTTP + flash, validazione file (dimensione, assenza), viewer 403, invariante righe totali |
| `LocalizationViewerTest` | 15 | Viewer EN/ES/null vedono il contenuto nella lingua corretta, locale non supportato fallback a italiano, persistenza su `users.locale`, `HasLocalePreference`, subject notifica in spagnolo, `validation.required` in tutte e tre le lingue, presenza chiavi viewer in tutti i locale |
| `CategoryTranslationTest` | 15 | `getLocalizedName()` + fallback, service upsert idempotente, autorizzazione admin/editor/viewer, `created_by`, update/delete, validazione locale (IT esclusa), pagina edit, cascade delete |
| `BookmarkTest` | 15 | Toggle add/remove, unique constraint, isolamento dati tra utenti, destroy 200/403, studio da bookmarks, redirect warning su lista vuota, cascade delete, accesso/redirect unauthenticated, filtri categoria e testo, saveNote Livewire (verifica pivot), validazione max 500 caratteri |
| `WebPushTest` | 14 | Subscribe/unsubscribe viewer, 403 su non-viewer, payload VAPID corretto, `toWebPush()` su `RegistrazioneApprovataNotification` e `BadgeEarned`, comando `push:send-review-reminders` (lazy, zero N+1), canale WebPush nella notifica reminder |
| `LocalizationBackendTest` | 14 | Admin EN/IT/ES vede view backend nella lingua corretta, intestazioni colonna DataTable localizzate, meta tag datatables-i18n presente, flash messages in locale corretto, `BackupFailed` renderizzata nel locale dell'admin, audit log stringhe localizzate, parità chiavi IT/EN/ES |
| `DrivingPracticeTest` | 14 | Seeder moduli B, CRUD moduli admin, autorizzazione `canManageDrivingModules()`, registrazione sessione instructor/admin, calcolo avanzamento zero N+1, vincolo FK (modulo non eliminabile con sessioni), validazione `StoreDrivingSessionRequest`, cascade delete su utente |
| `AuditLogTest` | 14 | Creazione log su CRUD, accesso admin-only, filtri (user_id, type, event, date), diff before/after, export Excel filtrato, gestione utenti anonimizzati |
| `SimulatorTest` | 13 | Accesso autenticato/anonimo, start con/senza pool, play con/senza sessione attiva, autosave con score ricalcolato + protezione cross-user, submit + redirect risultato, destroy sessione, result owner/foreign-user, log warning su categoria mancante, `withDefault` su `QuizAttempt::quiz` |
| `QuestionReportTest` | 13 | Invio Livewire valido + persistenza DB, validazione `body` (min 10) e `type` (enum), anti-spam 3 pending, index admin 200/403, accept/reject con `resolved_by`/`resolved_at`/`admin_note`, destroy, KPI `$stats` corretti, cascade delete su `Question`, view show senza form di gestione per report risolti, editor con `edit_question` può moderare |
| `LicenseTypeTest` | 13 | Seeder 17 tipi, migration retrocompatibilità (categorie e quiz assegnati a tipo B), CRUD admin, autorizzazione `canEditLicenseType()`, cascade delete, reversibilità migration, `syncCategories()` pivot |
| `CategoryMaterialTest` | 13 | Creazione di ogni tipo (note, link, PDF con upload), accesso negato a viewer, cascade delete categoria→materiali, eliminazione file fisico PDF, visibilità materiali nella pagina studio viewer, validazione file non-PDF e URL non valido, accessor YouTube embed, reorder |
| `ViewerLicenseTypeTest` | 13 | Redirect viewer senza `active_license_type_id`, filtri studio/simulatore/diagnostico/SM-2 per tipo patente, formato esame da `LicenseType`, validazione `UpdateActiveLicenseTypeRequest`, middleware `license.required` non blocca admin/editor |
| `SpacedRepetitionTest` | 12 | Algoritmo SM-2: prima/seconda/terza risposta corretta, reset su sbagliata, floor ease 1.30, ceiling ease 2.80, cap intervallo 365gg; integrazione: learned exclusion, upcoming count, studio crea review, quiz attempt crea review per ogni domanda |
| `ReportingTest` | 12 | Calcolo pass_rate/average_score, conteggio studenti distinti, periodo precedente + delta, ordinamento top domande, caching, accessi 403 viewer, HTTP admin (index/show/export-pdf), validazione date, filtro per tipo patente |
| `QuestionVersionTest` | 11 | Modifica testo crea versione, modifica campo non versionabile non crea versione, `question_version_id` registrato nel tentativo, dettaglio tentativo mostra testo storico dopo modifica, revisione errori mostra testo storico, tentativo legacy (senza version_id) fallback senza errori, ripristino crea V(n+1) senza cancellare V1/V2, data-migration idempotente V1 per domande esistenti, Livewire `QuestionVersionHistory::restoreVersion`, accessori `getAnswerVersionId` su formato flat e esteso |
| `GdprExportTest` | 10 | Struttura array export, utente anonimizzato, download viewer proprio, 403 su utente altrui, download admin, audit log profile, audit log admin, cleanup file vecchi, redirect guest, payload ZIP completo |
| `UserStatsTest` | 10 | Dashboard, aggregati, cache, invalidazione, vista admin |
| `RegistrationFlowTest` | 10 | Workflow iscrizione anagrafica end-to-end, stati pending/approved/rejected |
| `DrivingAttestationTest` | 10 | Download admin/instructor/viewer completato, 403 viewer non completato, gestione instructor null, cleanup PDF >24h, Content-Type PDF corretto, audit log generazione |
| `GdprTest` | 9 | PII anonimizzata, blocco admin, dry-run, login impossibile, sessioni DB, `gdpr:list`, `--anonymized` filter |
| `TtsPreferenceTest` | 8 | Abilitazione/disabilitazione TTS, autoplay toggle, accesso non-viewer 403, rendering condizionato pulsante "Ascolta", validazione `UpdateAccessibilityPreferencesRequest`, verifica colonne migration |
| `MediaManagerTest` | 8 | Render, switch folder, upload, duplicati, rename, delete |
| `LocaleTest` | 8 | Salvataggio sessione locale, validazione locale non supportato, applicazione middleware `SetLocale`, traduzioni IT/EN/ES, flash message, `test_switch_locale_to_spanish`, `test_menu_string_translated_to_spanish` |
| `AdminOperativityTest` | 8 | Export Excel, riepilogo KPI, schedulazione iscrizioni, comando `close-expired` |
| `MultiLicenseReportTest` | 7 | Filtri DataTable per tipo patente (domande/quiz/iscrizioni/utenti), aggregazione report con filtro licenza, retrocompatibilità senza filtro, header PDF con tipo patente, `reports:generate-by-license` |
| `ImportMultiLicenseTest` | 6 | Import multi-tipo (associazione pivot), retrocompat tipo B default, errori tipo non trovato, categorie condivise tra tipi, validazione `license_type_id` in `ImportQuestionsRequest` |
| `StudyPlanFeatureTest` | 10 | Ordinamento mastery ascendente, solo diagnostico, solo storico, senza dati (empty state + mastery 0), `recommended_action` per i tre livelli, cascata delete utente → `diagnostic_results` |
| `ProfileTest` | 5 | Profilo, aggiornamento, cancellazione account, TTS preferences, locale preferita |
| `CategoryTest` | 4 | CRUD, permessi |
| `QuestionTest` | 4 | CRUD domande, permessi |
| `DrivingSequentialityTest` | 13 | 422 su registrazione modulo fuori ordine (B senza A completato), sblocco B dopo completamento A, `getCompletionStatus` (all_completed/next_required/completion_date), view viewer banner certificazione/prossimo modulo, PDF `buildData` con `completion_status` |
| `StudyContentTest` | 9 | CRUD per ruolo (`canEditStudyContent`), `markAsRead` idempotente, `isReadBy`, cascade delete su categoria e su modulo |
| `FeatureToggleTest` | 10 | Toggle on/off via Livewire, `guest_homepage_enabled = false` → redirect login, `gamification_enabled = false` → badge 404, tentativo toggle su flag config → 422, accesso admin/non-admin, fallback default=true, setting=0, chiavi config-managed attese |
| `SystemSettingsTest` | 10 | Get/set service, Redis fallback, 403 editor, 6 indicatori health, salvataggio settings, upload logo, validazione hex/filesize, idempotenza seeder |
| `GuestHomeTest` | 12 | Risposta 200, nome scuola e tagline visibili, redirect per ruolo (admin/editor/viewer), sezione statistiche nascosta se tutti 0, sezione patenti nascosta se ≤ 1, view senza logo, view senza tagline, chiavi i18n nei tre locale |
| `AppearanceSettingsTest` | 5 | Salvataggio nuove chiavi appearance, validazione font, rendering `--sg-accent`, skin sidebar configurata, accesso non-admin negato |
| `AuthPagesLayoutTest` | 11 | Rotte `/login`, `/register`, `/forgot-password` rispondono 200, marker `guest-page` e `sg-auth-card` presenti, logo configurato visibile, flusso login valido, password errata rigettata, utente autenticato rediretto da `/login` |
| `ReadableTextColorTest` | 6 | `readableTextColor()`: giallo chiaro → `#212529`, blu scuro → `#ffffff`, accent default `#3c8dbc` → `#212529`, hex shorthand `#fff`, rosso puro, nero puro |
| `AdminPagesStructureTest` | 12 | 9 rotte migrate a `sg-wrapper` rispondono 200 per admin, assenza `var(--font-family)` in settings, `quiz.attempts.show` e `simulator.result` rispondono 200 |
| `CssCentralizationTest` | 13 | Homepage 200, assenza inline overhead (`[x-cloak]`, logo, sortable, hero overlay, navbar-height), view toccate 200, CSS con tutte le classi/variabili nuove, `welcome.blade.php` eliminata |
| `DesignSystemFoundationsTest` | 12 | Assenza controlli appearance nel pannello settings, token CSS centralizzati presenti, Inter caricata, migration `deprecate_appearance_settings` reversibile, assenza chiavi appearance in settings |
| `RedesignShellTest` | 11 | Shell navy uniforme (`--sg-shell-bg`), badge ruolo con `sg-role-{ruolo}`, classe `sg-navbar` presente, `role-{ruolo}` sul body, partial `role-badge` renderizzato |
| `RedesignComponentsTest` | 6 | Stat icon senza `grad-*` saturati, `sg-status-box` nei report segnalazioni, `sg-badge--pending` presente, assenza `table-warning` nelle righe segnalazioni |
| `RedesignGuestTest` | 11 | Homepage 200, struttura `.sg-hero` + `.sg-hero-bg` + `.sg-hero-overlay` + `.sg-hero-content`, assenza box annidati (`sg-hero-overlay-text`), feature card `.sg-feature-card`, CTA `.sg-cta-section`, login 200, register 200, flusso login |
| `I18nContentFixTest` | 5 | Chiavi `editor.reports_col_{type,reporter,date}` risolvono in `it`, `common.all` → "Tutti", homepage guest senza placeholder inglese |
| `Auth/*` | 16 | Login, logout, registrazione, reset password, verifica email, aggiornamento password, conferma password |

**Totale**: ~757 test in ~66 classi Feature (incluse `Auth/*`).

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
