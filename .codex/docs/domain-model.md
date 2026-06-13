# Domain Model — ScuolaGUIDA

Mappa delle entità principali, relazioni e tabelle pivot. Da usare come riferimento rapido prima di scrivere migrations, services o query.

## Entità principali

### User
- Ruoli: `admin`, `editor`, `viewer`, `instructor`
- Metodi ruolo: `isAdmin()`, `isEditor()`, `isViewer()`, `isInstructor()`
- Metodi permesso: `canEditQuestion()`, `canEditQuiz()`, `canEditCategory()`, `canEditUser()`, `canEditLicenseType()`, `canManageDrivingModules()`, `canRegisterDrivingSession()`, `canExportDrivingAttestation()`
- Campi notevoli: `role`, `registration_status` (none/pending/approved/rejected), `active_license_type_id`, `locale`, `tts_enabled`, `tts_autoplay`, `two_factor_secret`, `two_factor_recovery_codes`
- Relazioni: `licenseTypes()` M:M, `activeLicenseType()` BelongsTo, `students()` / `instructors()` M:M (pivot `instructor_student`), `instructorNotes()` HasMany, `badges()` HasMany `UserBadge`, `activityLog()` HasMany `UserActivityLog`, `pushSubscriptions()` (HasPushSubscriptions trait)
- Implementa `HasLocalePreference` → notifiche renderizzate nel locale dell'utente automaticamente

### Question
- Relazioni: `category()` BelongsTo, `versions()` HasMany `QuestionVersion`, `translations()` HasMany `QuestionTranslation`
- Campi versionabili (creano snapshot): `question`, `is_true`, `image`, `category_id`
- Metodo: `getLocalizedText(string $locale)` con fallback italiano

### Category
- Relazioni: `questions()` HasMany, `materials()` HasMany `CategoryMaterial`, `translations()` HasMany `CategoryTranslation`, `licenseTypes()` M:M (pivot `category_license_type`)
- Metodo: `getLocalizedName(string $locale)` con fallback italiano

### LicenseType
- Codici: AM, A1, A2, A, B, B96, BE, C1, C1E, C, CE, D1, D1E, D, DE, CQC (Merci), CQC (Persone)
- Campi formato esame: `exam_questions`, `exam_minutes`, `exam_max_errors`
- Relazioni: `categories()` M:M, `quizzes()` HasMany, `drivingModules()` HasMany
- Scope: `active()`

### Quiz
- Stati: `draft` → `published` → `confirmed`
- Relazioni: `questions()` M:M (pivot `quiz_question` con `order`), `attempts()` HasMany, `licenseType()` BelongsTo

### QuizAttempt
- Formato `answers` JSON: array di oggetti `{question_id, user_answer, is_correct, time_spent, question_version_id}`
- `question_version_id` aggiunto da `QuizAttemptService::injectVersionIds()`
- Relazioni: `quiz()` BelongsTo (con `withDefault`), `user()` BelongsTo

### DrivingModule
- Relazioni: `licenseType()` BelongsTo, `drivingSessions()` HasMany
- Scope: `ordered()`

### DrivingSession
- Relazioni: `student()`, `instructor()`, `drivingModule()`, `recorder()` — tutte BelongsTo User

## Tabelle pivot / aggiuntive

| Tabella | Collega | Note |
|---|---|---|
| `quiz_question` | Quiz ↔ Question | ha `order` |
| `category_license_type` | Category ↔ LicenseType | — |
| `instructor_student` | User (instructor) ↔ User (student) | `assigned_at`, `assigned_by` |
| `question_reviews` | SM-2 per ogni (user, question) | `ease_factor`, `interval`, `next_review_at`, ... |
| `learned_questions` | User ↔ Question (imparate) | — |
| `bookmarks` | User ↔ Question | `note` max 500 |
| `user_badges` | User + badge_code | unique (user_id, badge_code) |
| `user_activity_log` | User + activity_date | unique (user_id, activity_date) |
| `instructor_notes` | instructor_id + student_id | `body`, `created_by` |
| `push_subscriptions` | User (subscribable) | via trait `HasPushSubscriptions` |

## Regole FK obbligatorie

- Tutte le FK verso `users` → `cascadeOnDelete` (requisito GDPR: anonimizzazione propagata)
- `driving_sessions.driving_module_id` → `restrict` (modulo non eliminabile con sessioni)
- `category_license_type` → `cascadeOnDelete` su entrambi i lati
