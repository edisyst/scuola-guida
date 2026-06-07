# Services Map — ScuolaGUIDA

Elenco di tutti i service in `app/Services/` con responsabilità principali. Consultare prima di aggiungere logica nei controller.

## Service esistenti

| Service | Responsabilità |
|---|---|
| `QuestionService` | CRUD domande, bulk delete, import MIT, crea snapshot versione su create/update |
| `QuizService` | CRUD quiz, gestione domande (add/remove/reorder), transizioni di stato |
| `QuizAttemptService` | Avvio/submit tentativo, calcolo score, autosave, `injectVersionIds()`, dispatch notifiche istruttori |
| `QuizSummaryService` | Aggregati per riepilogo quiz confermato (pass_rate, punteggio medio, tabella iscritti) |
| `StudyService` | `questionsFromCategory()`, `randomQuestions()` — filtrati per `activeLicenseType` del viewer |
| `SimulatorService` | `buildQuestionList()`, format esame da LicenseType (`getExamQuestionsCount/Minutes/MaxErrors()`), `getResultDetail()` |
| `DiagnosticService` | `generateQuestions()` (una per categoria, esclude viste 24h), `saveResults()`, `hasDiagnostic()` |
| `SpacedRepetitionService` | Algoritmo SM-2, `recordAnswer()`, `getDueQuestions()`, `getDueCountByCategory()`, `getUpcomingCount()` — filtrati per licenseType |
| `ReviewErrorsService` | Aggregazione errori degli ultimi N tentativi, `markAsLearned()`, `unmarkAsLearned()`, `getErrorCount()` cached |
| `StudyPlanService` | Calcolo mastery per categoria (70% storico + 30% diagnostico), `recommended_action` |
| `StreakService` | `recordActivity()`, `getCurrentStreak()`, `getLongestStreak()`, `getStats()` cached |
| `BadgeService` | `checkAllBadges()` con short-circuit, `awardIfEligible()` idempotente + dispatch `BadgeEarned` |
| `NotificationService` | `sendToUser()`, `sendToAdmins()` — dispatch queued su `emails` |
| `UserStatsService` | Aggregati dashboard viewer (`get()`, `forget()`), cache `user_stats_{id}` TTL 600s |
| `DashboardStatsService` | KPI globali admin/editor (`kpi()`, `dailyCreated()`), cache con invalidazione via Observer |
| `AuditLogService` | `query()` filtrato, `getAuditableTypes()`, `getDiff()`, `formatUser()`, `typeLabel()` |
| `ReportingService` | `buildPeriodReport(from, to, ?LicenseType)`, `buildComparisonReport()`, cache 24h/5min |
| `EditorMetricsService` | `getProductionMetrics(?editor, from, to, ?LicenseType)`, `getGlobalContentMetrics(?LicenseType)` |
| `HealthService` | `getBackupStatus()`, `getDatabaseSize()`, `getStorageSize()`, `getQueueStatus()`, `getDiskSpace()`, `getRecentErrors()` |
| `GdprExportService` | `buildExport(User)`, `generateZip(User)`, `cleanupOldExports()` |
| `LicenseTypeService` | CRUD tipi patente, `syncCategories()`, `allForSelect()` |
| `InstructorService` | `assignStudent()`, `unassignStudent()`, `getStudentProgress()`, `getInstructorOverview()`, `addNote()`, `deleteNote()`, `getNotesForStudent()`, `prepareStudentExportData()` |
| `DrivingModuleService` | CRUD moduli, `delete()` con guard (no sessioni esistenti) |
| `DrivingSessionService` | `record()`, `delete()`, `getProgress()`, `canRegisterForStudent()` |
| `DrivingAttestationService` | `buildData()` (zero N+1), `generatePdf()`, salva in `storage/private/driving-attestations/` |
| `MitImportService` | `import(array, LicenseType)`, `processRow()` — deduplication su `mit_code`, `syncWithoutDetaching` categorie |
| `QuestionVersionService` | `snapshotIfChanged()`, `buildVersionMapForAttempt()`, `latestVersionIdMap()`, `restoreVersion()`, `isHistoricalVersion()` |
| `CategoryTranslationService` | `upsert(category, locale, name)` idempotente, `delete()`, `getForCategory()` |
| `QuestionTranslationService` | `upsert(question, locale, text)` idempotente, `delete()`, `getForQuestion()` |

## Pattern di injection

I service vengono iniettati nel costruttore del controller o come parametro del metodo. In entrambi i casi Laravel li risolve automaticamente via container.

```php
// Costruttore
public function __construct(private QuestionService $service) {}

// Parametro metodo (preferito per azioni singole)
public function store(StoreQuestionRequest $request, QuestionService $service)
```

## Cache — chiavi e TTL

| Chiave | TTL | Invalidata da |
|---|---|---|
| `admin_badges` | 60s | `QuizObserver`, `QuestionObserver`, `CategoryObserver`, `UserObserver` su created/updated/deleted |
| `user_stats_{id}` | 600s | `QuizAttempt::booted()` su saved/deleted |
| `dashboard_kpi` | 300s | stessi 4 Observer |
| `sr_upcoming_{id}` | 300s | `SpacedRepetitionService::recordAnswer/markAsLearned/unmarkAsLearned` |
| `streak_{id}` | dinamico (fino a mezzanotte) | `StreakService::recordActivity()` |
| `review_errors_count_{id}` | 600s | `QuizAttemptService::record()`, mark/unmark learned |
| `earned_badges_{id}` | 1800s | `BadgeService::awardIfEligible()` |
| `notif_unread_{id}` | 30s | `markAsRead()`, `markAllAsRead()` |
| `editor_metrics_*` | 86400s (passato) / 300s (corrente) | — (TTL sufficiente) |
| Report `period_*` | 86400s (passato) / 300s (corrente) | — |
