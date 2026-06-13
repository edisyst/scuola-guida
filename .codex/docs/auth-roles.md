# Autenticazione, Ruoli e Permessi

## Ruoli

| Costante | Stringa | Accesso |
|---|---|---|
| `User::ROLE_ADMIN` | `admin` | Tutto |
| `User::ROLE_EDITOR` | `editor` | Gestione contenuti + report; 2FA obbligatoria |
| `User::ROLE_VIEWER` | `viewer` | Solo area studio/quiz personale |
| `User::ROLE_INSTRUCTOR` | `instructor` | Progressi studenti assegnati; read-only su contenuti |

## Metodi di controllo ruolo

```php
$user->isAdmin()       // role === 'admin'
$user->isEditor()      // role === 'editor'
$user->isViewer()      // role === 'viewer'
$user->isInstructor()  // role === 'instructor'
```

**Non usare mai** `$user->hasRole()` di Spatie: il progetto non usa Spatie.

## Metodi di permesso

```php
$user->canEditQuestion()      // admin + utenti con permesso edit_question
$user->canEditQuiz()          // admin + utenti con permesso edit_quiz
$user->canEditCategory()      // admin + utenti con permesso edit_category
$user->canEditUser()          // admin + utenti con permesso edit_user
$user->canEditLicenseType()   // solo admin
$user->canManageDrivingModules()   // solo admin
$user->canRegisterDrivingSession() // admin o instructor
$user->canExportDrivingAttestation() // admin o instructor
```

Il ruolo `instructor` ha tutti `canEditXxx()` = false (nessun permesso di modifica contenuti).

## Pattern di autorizzazione nei controller

```php
// Sempre abort_unless, mai policy/gate inline
abort_unless(auth()->user()->canEditQuestion(), 403);

// Per le rotte admin/editor
abort_unless(auth()->user()->isAdmin() || auth()->user()->isEditor(), 403);

// Viewer solo i propri dati
abort_unless($attempt->user_id === auth()->id() || auth()->user()->canEditUser(), 403);
```

## Middleware registrati

| Alias | Classe | Uso |
|---|---|---|
| `role` | `RoleMiddleware` | `role:admin` / `role:admin,editor` / `role:admin,instructor` |
| `2fa` | `EnsureTwoFactorAuthenticated` | Obbligatorio per admin/editor |
| `license.required` | `RequireLicenseType` | Reindirizza viewer senza `active_license_type_id` |

## Gates registrati in AppServiceProvider

| Gate | Condizione |
|---|---|
| `admin-only` | `isAdmin()` |
| `content-editor` | `isAdmin() || isEditor()` |
| `exam-participant` | `isViewer()` |
| `is-instructor` | `isInstructor()` |
| `instructor-area` | `isInstructor() || isAdmin()` |
| `view-question-reports` | `canEditQuestion()` |

## 2FA (TOTP)

- Obbligatoria per `admin` ed `editor`; skippata per `viewer` e `instructor`
- Il middleware `EnsureTwoFactorAuthenticated` verifica `session('2fa_verified')` dopo il login
- Setup: `/2fa/setup` — genera QR code SVG (`bacon/bacon-qr-code`) con la secret TOTP
- Challenge: `/2fa/challenge` — verifica OTP o codice di emergenza (monouso)
- Recovery: `php artisan 2fa:reset {user_id}`
- **Nei test**: `withoutMiddleware(EnsureTwoFactorAuthenticated::class)` nel `setUp()` di ogni test admin

## GDPR

- **Anonimizzazione** (art. 17): `gdpr:anonymize {id}` sovrascrive tutti i PII con `[anonimizzato]`, blocca il login, invalida le sessioni
- **Portabilità** (art. 20): ZIP scaricabile da `/profile/download-data` (viewer) o `/admin/users/{id}/download-data` (admin). Ogni export tracciato in audit log, file eliminato dopo il download
- **Cascade**: tutte le FK verso `users` usano `cascadeOnDelete` come regola di progetto per preservare coerenza GDPR nelle cancellazioni/cleanup collegati
