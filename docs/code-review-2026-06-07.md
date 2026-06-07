# Code Review & Test Audit — 2026-06-07

> Branch: `develop` · Commit: `c5d3ab8`
> Esecutore: Claude Code (claude-sonnet-4-6)
> Suite locale: **618 passed / 1513 assertions** — verde prima e dopo i fix

---

## 1. Riepilogo violazioni architetturali

| Categoria | Trovate | Fixate | Aperte |
|-----------|---------|--------|--------|
| Sicurezza (XSS) | 1 | 1 | 0 |
| Hardcoded configurable values | 3 | 0 | 3 |
| **Totale** | **4** | **1** | **3** |

### 1a. Fix applicato — XSS in editor/dashboard

**File**: `resources/views/editor/dashboard.blade.php:82`

**Violazione**: `$editor->name` (input utente) usato direttamente in un contesto `{!! !!}` senza escaping. Un editor con un nome contenente `<script>` o tag HTML arbitrari avrebbe potuto iniettare codice nella pagina.

```diff
- {!! __('editor.production_by', ['name' => '<strong>'.$editor->name.'</strong>']) !!}
+ {!! __('editor.production_by', ['name' => '<strong>'.e($editor->name).'</strong>']) !!}
```

### 1b. Violazioni aperte — Hardcoded values (bassa gravità)

Non fixate perché richiedono la creazione di file di config nuovi (`config/badges.php`, aggiornamento di `config/media.php`) e un allineamento con il resto del codebase: lavoro da fare in una PR dedicata di cleanup, non urgente.

| File | Riga | Valore hardcoded | Config suggerita |
|------|------|-----------------|-----------------|
| `app/Services/BadgeService.php` | ~64–99 | Soglie badge (7/30/100 streak, 100/500/1000 domande) | `config/badges.php` |
| `app/Http/Livewire/Admin/MediaManager.php` | 18, 103 | `max:2048`, array extensions | `config/media.php` |
| `app/Http/Livewire/NotificationBell.php` | 11 | `const UNREAD_CACHE_TTL = 30` | `config/cache.php` |

### 1c. Nessuna violazione trovata in

- Controller: zero logica di business, zero `validate()` inline ✓
- Livewire: `#[Validate]` su tutte le property, zero `rules()` ✓
- Livewire: `wire:model.blur` usato correttamente, zero `.defer` / `.live` ✓
- Livewire: `wire:loading` presente su tutti i bottoni azione ✓
- Migration: tutti i `down()` implementati e reversibili ✓
- Migration: tutte le FK primarie verso `users` con `cascadeOnDelete()` ✓ (le FK audit/reviewer usano `nullOnDelete()` — semanticamente corretto)
- Notifiche: tutte dispatchate dai Service, mai dai controller ✓
- Notifiche: tutte con `->onQueue('emails')` e `implements ShouldQueue` ✓
- View: tutti gli `{!! !!}` su input utente ora escaped ✓; gli altri sono su stringhe di traduzione o HTML generato dal codice ✓
- Script: tutti i `<script>` sono in `@section('js')/@parent` o `@push('scripts')` ✓ — nessun inline nel body

---

## 2. Stato test suite

| | Prima | Dopo |
|--|-------|------|
| Test totali | 618 | 618 |
| Passed | 618 | 618 |
| Failed | 0 | 0 |
| Assertion | 1513 | 1513 |
| Test aggiunti | — | 0 |
| Test corretti | — | 0 |

**Osservazioni sulla qualità della suite**:
- RefreshDatabase: 51/51 file (100%) ✓
- Nessun `markTestSkipped()` non documentato ✓
- Nessuna assertion debole (`assertTrue(true)`, test vuoti) ✓
- Ogni feature copre happy path + almeno un caso di errore ✓
- 7 file usano `Livewire::test()` per i componenti Livewire ✓

---

## 3. Tabella copertura feature — stato finale

| Feature | Test presente | File | N. test |
|---------|:---:|-------|---------|
| Quiz lifecycle | ✅ | `QuizTest.php` | 21 |
| Viewer enrollment / iscrizione | ✅ | `RegistrationFlowTest.php` | 9 |
| Media Manager | ✅ | `MediaManagerTest.php` | 8 |
| Audit log | ✅ | `AuditLogTest.php` | 14 |
| Excel import (MIT) | ✅ | `MitImportTest.php` | 14 |
| Excel import (MultiLicense) | ✅ | `ImportMultiLicenseTest.php` | 4 |
| GDPR anonymization | ✅ | `GdprTest.php` | 7 |
| GDPR export | ✅ | `GdprExportTest.php` | 10 |
| 2FA admin/editor | ✅ | `TwoFactorTest.php` | 18 |
| Instructor role (base) | ✅ | `InstructorTest.php` | 20 |
| i18n backend (IT/EN/ES) | ✅ | `LocalizationBackendTest.php` | 13 |
| i18n viewer | ✅ | `LocalizationViewerTest.php` | 12 |
| Multi-license-type | ✅ | `LicenseTypeTest.php` | 9 |
| Multi-license report | ✅ | `MultiLicenseReportTest.php` | 6 |
| MIT import (`questions:import-mit`) | ✅ | `MitImportTest.php` | 14 |
| DrivingModule / DrivingSession | ✅ | `DrivingPracticeTest.php` | 15 |
| DrivingAttestationService (PDF) | ✅ | `DrivingAttestationTest.php` | 8 |
| Notifiche in-app e email | ✅ | `NotificationsTest.php` | 20 |
| Web Push notifications | ✅ | `WebPushTest.php` | 14 |
| Spaced repetition (SR) | ✅ | `SpacedRepetitionTest.php` | 14 |
| Gamification (badge/streak) | ✅ | `GamificationTest.php` | 22 |
| Review errors | ✅ | `ReviewErrorsTest.php` | 17 |
| Question versioning | ✅ | `QuestionVersionTest.php` | 10 |
| Simulator | ✅ | `SimulatorTest.php` | 13 |
| Study session | ✅ | `StudyTest.php` | 12 |
| Study plan | ✅ | `StudyPlanFeatureTest.php` | 13 |
| Offline API | ✅ | `OfflineApiTest.php` | 20 |
| TTS preferences | ✅ | `TtsPreferenceTest.php` | 8 |
| Viewer license type filter | ✅ | `ViewerLicenseTypeTest.php` | 14 |
| Health / Backup dashboard | ✅ | `HealthTest.php` | 12 |
| Editor dashboard | ✅ | `EditorDashboardTest.php` | 13 |
| Bookmark | ✅ | `BookmarkTest.php` | 13 |
| Category materials | ✅ | `CategoryMaterialTest.php` | 13 |
| Calendar | ✅ | `CalendarTest.php` | 14 |
| Diagnostic | ✅ | `DiagnosticFeatureTest.php` | 13 |
| User stats | ✅ | `UserStatsTest.php` | 11 |
| Reporting (PDF) | ✅ | `ReportingTest.php` | 11 |
| Question translations | ✅ | `QuestionTranslationTest.php` | 13 |
| Category translations | ✅ | `CategoryTranslationTest.php` | 11 |
| Question reports | ✅ | `QuestionReportTest.php` | 14 |

**Copertura stimata**: 41/41 feature con almeno un test Feature ✓

---

## 4. Divergenze CI/locale risolte

### Problema

Il workflow `.github/workflows/tests.yml` montava un servizio MySQL 8, impostava le env `DB_CONNECTION=mysql` e eseguiva `php artisan migrate --force` su MySQL. Tuttavia `phpunit.xml` ha `force="true"` su `DB_CONNECTION=sqlite` e `DB_DATABASE=:memory:`, che sovrascrive qualsiasi env impostata dal workflow durante l'esecuzione dei test.

**Effetto**: i test hanno sempre usato SQLite (`:memory:`) sia in locale che in CI. Il MySQL service, i 5 env MySQL nel job, lo step "Wait for MySQL" e lo step "Run migrations" erano **dead code** — consumavano ~30-40 secondi di CI senza produrre alcun effetto sui test.

### Fix applicato

`tests.yml` modificato: rimossi `services.mysql`, le 5 env DB MySQL, lo step "Wait for MySQL" e lo step "Run migrations". L'estensione `pdo_mysql` rimossa dall'elenco PHP extensions (ora non più necessaria).

Il workflow ora è più leggero e la sua configurazione corrisponde a ciò che i test effettivamente fanno: SQLite in-memory tramite phpunit.xml.

### Nota

Se in futuro si vuole far girare i test su MySQL in CI per rilevare divergenze MySQL-vs-SQLite (es. JSON functions, full-text search), il percorso corretto è rimuovere il `force="true"` da phpunit.xml e ripristinare il servizio MySQL nel workflow. Questa è una decisione di product/infra che esula dal presente audit.

---

## 5. Known issues

Tutti i known issues aperti in CLAUDE.md sono stati verificati:

| Issue | Stato | Evidenza |
|-------|-------|---------|
| `View::composer('*', ...)` | ✅ CHIUSO | `AppServiceProvider.php:158,181` — ora su `layouts.admin` |
| `ImportQuestionsRequest` senza `max:5120` | ✅ CHIUSO | `ImportQuestionsRequest.php:17` — presente |
| Migration `drop_quiz_results_table` | ✅ CHIUSO | `2026_06_04_225529_drop_quiz_results_table.php` |
| `Quiz::hasQuestion()` senza type hint | ✅ CHIUSO | `Quiz.php:51` — `int\|string $questionId): bool` |
| `QuizAttemptService::scoreAnswers()` senza type hint | ✅ CHIUSO | `QuizAttemptService.php:215` — `: int` |
| `RoleMiddleware::handle()` senza return type | ✅ CHIUSO | `RoleMiddleware.php:11` — `): Response` |

Nessun known issue peggiorato.

---

## 6. Azioni manuali richieste

Nessuna azione manuale urgente. Le seguenti sono suggerite per PRs future:

1. **PR cleanup — hardcoded values** (bassa priorità):
   - Creare `config/badges.php` con soglie badge e refactor `BadgeService`
   - Aggiornare `config/media.php` con `max_upload_size` e `allowed_extensions`, refactor `MediaManager`
   - Spostare `UNREAD_CACHE_TTL` in `config/cache.php`, refactor `NotificationBell`

2. **Decisione infra — MySQL in CI** (media priorità, se rilevante):
   - Se si vuole coprire regressioni MySQL-specific (JSON functions, collazioni), ripristinare MySQL in CI rimuovendo `force="true"` da phpunit.xml. Attenzione: i test con SQLite-only work-around (es. `whereJsonLength` non usato) potrebbero richiedere rework.
