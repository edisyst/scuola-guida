<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\InstructorNote;
use NotificationChannels\WebPush\HasPushSubscriptions;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasPushSubscriptions;

    const ROLE_ADMIN      = 'admin';
    const ROLE_EDITOR     = 'editor';
    const ROLE_VIEWER     = 'viewer';
    const ROLE_INSTRUCTOR = 'instructor';

    /*
    |--------------------------------------------------------------------------
    | STATI ISCRIZIONE DEFINITIVA (viewer)
    |--------------------------------------------------------------------------
    | Solo i viewer hanno un ciclo di iscrizione anagrafica con approvazione admin.
    | Un viewer non approvato può esercitarsi liberamente coi quiz casuali ma non
    | può iscriversi agli esami ufficiali. Se modifica i dati dopo l'approvazione
    | e li reinvia, lo stato torna a 'pending' e perde l'abilitazione agli esami.
    */

    public const REG_NONE     = 'none';
    public const REG_PENDING  = 'pending';
    public const REG_APPROVED = 'approved';
    public const REG_REJECTED = 'rejected';

    public const REG_STATUSES = [
        self::REG_NONE     => 'Da compilare',
        self::REG_PENDING  => 'In attesa',
        self::REG_APPROVED => 'Approvata',
        self::REG_REJECTED => 'Rifiutata',
    ];

    /*
    |--------------------------------------------------------------------------
    | CATALOGO PERMESSI
    |--------------------------------------------------------------------------
    | Pattern: {action}_{entity}
    |
    | Tutte le actions sono configurabili dalla UI dei ruoli e persistite nel DB.
    | Admin riceve sempre l'elenco completo via allPermissions().
    | manage_{entity} = bypass totale su quella entità (include read e bulk).
    */

    public const ENTITIES = ['question', 'quiz', 'category', 'user'];
    public const ACTIONS  = ['read', 'create', 'edit', 'delete', 'bulk', 'manage'];

    /** Alias: tutte le actions sono ora gestite dalla UI dei ruoli */
    public const MANAGED_ACTIONS = self::ACTIONS;

    public const LABELS = [
        'question' => 'Domande',
        'quiz'     => 'Quiz',
        'category' => 'Categorie',
        'user'     => 'Utenti',
    ];

    public const ACTION_LABELS = [
        'read'   => 'Leggi',
        'create' => 'Crea',
        'edit'   => 'Modifica',
        'delete' => 'Elimina',
        'bulk'   => 'Operazioni bulk',
        'manage' => 'Gestisci (tutto)',
    ];

    /** Alias: l'UI dei ruoli mostra tutte le actions */
    public const MANAGED_ACTION_LABELS = self::ACTION_LABELS;

    public const ROLES = [
        self::ROLE_ADMIN       => 'Admin',
        self::ROLE_EDITOR      => 'Editor',
        self::ROLE_VIEWER      => 'Viewer',
        self::ROLE_INSTRUCTOR  => 'Istruttore',
    ];

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'permissions',
        // Dati anagrafici per iscrizione esami ufficiali
        'first_name',
        'last_name',
        'address',
        'birth_date',
        'birth_place',
        'fiscal_code',
        'id_document_path',
        'registration_status',
        'registration_submitted_at',
        'registration_reviewed_at',
        'registration_reviewed_by',
        'registration_rejection_reason',
        // Lingua preferita per il testo delle domande (Feature 7.1)
        'locale',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at'          => 'datetime',
            'password'                   => 'hashed',
            'permissions'                => 'array',
            'birth_date'                 => 'date',
            'registration_submitted_at'  => 'datetime',
            'registration_reviewed_at'   => 'datetime',
            'two_factor_secret'          => 'encrypted',
            'two_factor_recovery_codes'  => 'encrypted:array',
            'two_factor_enabled_at'      => 'datetime',
            'locale'                     => 'string',
        ];
    }

    /**
     * Lingua preferita per il testo delle domande (Feature 7.1).
     * Fallback alla lingua di default dell'applicazione se non impostata.
     */
    public function getPreferredLocale(): string
    {
        // Il testo originale delle domande è sempre italiano (MIT): 'it' come base
        // è una proprietà del contenuto, non della lingua dell'interfaccia.
        return $this->locale ?? 'it';
    }

    /*
    |--------------------------------------------------------------------------
    | RELATIONS
    |--------------------------------------------------------------------------
    */

    public function quizAttempts()
    {
        return $this->hasMany(QuizAttempt::class);
    }

    public function registrationReviewer()
    {
        return $this->belongsTo(User::class, 'registration_reviewed_by');
    }

    public function bookmarkedQuestions(): BelongsToMany
    {
        return $this->belongsToMany(Question::class, 'question_user_bookmarks')
                    ->withPivot('note')
                    ->withTimestamps()
                    ->orderByPivot('created_at', 'desc');
    }

    public function questionReports(): HasMany
    {
        return $this->hasMany(QuestionReport::class);
    }

    public function learnedQuestions(): HasMany
    {
        return $this->hasMany(LearnedQuestion::class);
    }

    public function students(): BelongsToMany
    {
        return $this->belongsToMany(
            User::class,
            'instructor_student',
            'instructor_id',
            'student_id'
        )->withPivot(['assigned_at', 'assigned_by'])->withTimestamps();
    }

    public function instructors(): BelongsToMany
    {
        return $this->belongsToMany(
            User::class,
            'instructor_student',
            'student_id',
            'instructor_id'
        )->withPivot(['assigned_at', 'assigned_by'])->withTimestamps();
    }

    public function hasStudent(User $student): bool
    {
        return $this->students()->where('student_id', $student->id)->exists();
    }

    public function instructorNotes(): HasMany
    {
        return $this->hasMany(InstructorNote::class, 'instructor_id');
    }

    /*
    |--------------------------------------------------------------------------
    | REGISTRAZIONE DEFINITIVA (viewer)
    |--------------------------------------------------------------------------
    */

    /**
     * Solo i viewer hanno l'obbligo di completare l'iscrizione anagrafica.
     * Admin ed editor non partecipano agli esami ufficiali.
     */
    public function requiresRegistration(): bool
    {
        return $this->isViewer();
    }

    public function isRegistrationApproved(): bool
    {
        return $this->registration_status === self::REG_APPROVED;
    }

    public function isRegistrationPending(): bool
    {
        return $this->registration_status === self::REG_PENDING;
    }

    public function isRegistrationRejected(): bool
    {
        return $this->registration_status === self::REG_REJECTED;
    }

    public function hasSubmittedRegistration(): bool
    {
        return in_array($this->registration_status, [
            self::REG_PENDING,
            self::REG_APPROVED,
            self::REG_REJECTED,
        ], true);
    }

    /**
     * Può iscriversi agli esami ufficiali per la patente?
     * Solo i viewer con registrazione approvata. Admin/editor restano esclusi
     * per design (la feature è riservata agli studenti).
     */
    public function canEnrollOfficialExams(): bool
    {
        return $this->isViewer() && $this->isRegistrationApproved();
    }

    public function fullAnagraphicName(): string
    {
        $parts = array_filter([$this->first_name, $this->last_name]);
        return $parts ? implode(' ', $parts) : $this->name;
    }

    /*
    |--------------------------------------------------------------------------
    | TWO-FACTOR AUTHENTICATION
    |--------------------------------------------------------------------------
    */

    public function hasTwoFactorEnabled(): bool
    {
        return ! is_null($this->two_factor_secret) && ! is_null($this->two_factor_enabled_at);
    }

    public function requiresTwoFactor(): bool
    {
        return config('two_factor.enabled') && ($this->isAdmin() || $this->isEditor());
    }

    public function generateRecoveryCodes(int $count = 8): array
    {
        return collect(range(1, $count))
            ->map(fn () => strtoupper(Str::random(5)) . '-' . strtoupper(Str::random(5)))
            ->toArray();
    }

    /*
    |--------------------------------------------------------------------------
    | ROLE CHECKS
    |--------------------------------------------------------------------------
    */

    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    public function isEditor(): bool
    {
        return $this->role === self::ROLE_EDITOR;
    }

    public function isViewer(): bool
    {
        return $this->role === self::ROLE_VIEWER;
    }

    public function isInstructor(): bool
    {
        return $this->role === self::ROLE_INSTRUCTOR;
    }

    /*
    |--------------------------------------------------------------------------
    | PERMESSI — API GENERICA
    |--------------------------------------------------------------------------
    */

    public static function allPermissions(): array
    {
        $perms = [];
        foreach (self::ENTITIES as $entity) {
            foreach (self::ACTIONS as $action) {
                $perms[] = "{$action}_{$entity}";
            }
        }
        return $perms;
    }

    /**
     * Permessi configurabili dal pannello ruoli (oggi: tutti).
     */
    public static function managedPermissions(): array
    {
        return self::allPermissions();
    }

    /**
     * Permessi associati al ruolo (cache 60s)
     */
    public static function rolePermissions(string $role): array
    {
        if ($role === self::ROLE_ADMIN) {
            return self::allPermissions();
        }

        return Cache::remember("role_perms_{$role}", 60, function () use ($role) {
            return RolePermission::where('role', $role)
                ->pluck('permission')
                ->toArray();
        });
    }

    /**
     * Permessi effettivi: ruolo + override individuali
     */
    public function effectivePermissions(): array
    {
        if ($this->isAdmin()) {
            return self::allPermissions();
        }

        $rolePerms = self::rolePermissions($this->role);
        $userPerms = $this->permissions ?? [];

        return array_values(array_unique(array_merge($rolePerms, $userPerms)));
    }

    public function hasPermission(string $permission): bool
    {
        if ($this->isAdmin()) {
            return true;
        }

        $effective = $this->effectivePermissions();

        // manage_{entity} fa da bypass per tutte le action sulla stessa entità.
        if (str_contains($permission, '_')) {
            [, $entity] = explode('_', $permission, 2);
            if (in_array("manage_{$entity}", $effective, true)) {
                return true;
            }
        }

        return in_array($permission, $effective, true);
    }

    /*
    |--------------------------------------------------------------------------
    | HELPERS — QUESTION
    |--------------------------------------------------------------------------
    */

    public function canReadQuestion(): bool   { return $this->hasPermission('read_question'); }
    public function canCreateQuestion(): bool { return $this->hasPermission('create_question'); }
    public function canEditQuestion(): bool   { return $this->hasPermission('edit_question'); }
    public function canDeleteQuestion(): bool { return $this->hasPermission('delete_question'); }
    public function canBulkQuestion(): bool   { return $this->hasPermission('bulk_question'); }
    public function canManageQuestion(): bool { return $this->hasPermission('manage_question'); }

    /*
    |--------------------------------------------------------------------------
    | HELPERS — QUIZ
    |--------------------------------------------------------------------------
    */

    public function canReadQuiz(): bool   { return $this->hasPermission('read_quiz'); }
    public function canCreateQuiz(): bool { return $this->hasPermission('create_quiz'); }
    public function canEditQuiz(): bool   { return $this->hasPermission('edit_quiz'); }
    public function canDeleteQuiz(): bool { return $this->hasPermission('delete_quiz'); }
    public function canBulkQuiz(): bool   { return $this->hasPermission('bulk_quiz'); }
    public function canManageQuiz(): bool { return $this->hasPermission('manage_quiz'); }

    /*
    |--------------------------------------------------------------------------
    | HELPERS — CATEGORY
    |--------------------------------------------------------------------------
    */

    public function canReadCategory(): bool   { return $this->hasPermission('read_category'); }
    public function canCreateCategory(): bool { return $this->hasPermission('create_category'); }
    public function canEditCategory(): bool   { return $this->hasPermission('edit_category'); }
    public function canDeleteCategory(): bool { return $this->hasPermission('delete_category'); }
    public function canBulkCategory(): bool   { return $this->hasPermission('bulk_category'); }
    public function canManageCategory(): bool { return $this->hasPermission('manage_category'); }

    /*
    |--------------------------------------------------------------------------
    | HELPERS — USER
    |--------------------------------------------------------------------------
    */

    public function canReadUser(): bool   { return $this->hasPermission('read_user'); }
    public function canCreateUser(): bool { return $this->hasPermission('create_user'); }
    public function canEditUser(): bool   { return $this->hasPermission('edit_user'); }
    public function canDeleteUser(): bool { return $this->hasPermission('delete_user'); }
    public function canBulkUser(): bool   { return $this->hasPermission('bulk_user'); }
    public function canManageUser(): bool { return $this->hasPermission('manage_user'); }
}
