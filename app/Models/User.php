<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Cache;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    const ROLE_ADMIN = 'admin';
    const ROLE_EDITOR = 'editor';
    const ROLE_VIEWER = 'viewer';

    /*
    |--------------------------------------------------------------------------
    | CATALOGO PERMESSI
    |--------------------------------------------------------------------------
    | Pattern: {action}_{entity} dove action ∈ {create, edit, delete, manage}
    | manage_{entity} = bypass totale sull'entità
    */

    public const ENTITIES = ['question', 'quiz', 'category', 'user'];
    public const ACTIONS  = ['create', 'edit', 'delete', 'manage'];

    public const LABELS = [
        'question' => 'Domande',
        'quiz'     => 'Quiz',
        'category' => 'Categorie',
        'user'     => 'Utenti',
    ];

    public const ACTION_LABELS = [
        'create' => 'Crea',
        'edit'   => 'Modifica',
        'delete' => 'Elimina',
        'manage' => 'Gestisci (tutto)',
    ];

    public const ROLES = [
        self::ROLE_ADMIN  => 'Admin',
        self::ROLE_EDITOR => 'Editor',
        self::ROLE_VIEWER => 'Viewer',
    ];

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'permissions',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'permissions' => 'array',
        ];
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

    public function canEdit(): bool
    {
        return in_array($this->role, [
            self::ROLE_ADMIN,
            self::ROLE_EDITOR
        ]);
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

        // manage_{entity} fa da bypass per la stessa entità
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
    | HELPERS — QUESTION (retrocompatibilità)
    |--------------------------------------------------------------------------
    */

    public function canCreateQuestion(): bool { return $this->hasPermission('create_question'); }
    public function canEditQuestion(): bool   { return $this->hasPermission('edit_question'); }
    public function canDeleteQuestion(): bool { return $this->hasPermission('delete_question'); }
    public function canManageQuestion(): bool { return $this->hasPermission('manage_question'); }

    /*
    |--------------------------------------------------------------------------
    | HELPERS — QUIZ
    |--------------------------------------------------------------------------
    */

    public function canCreateQuiz(): bool { return $this->hasPermission('create_quiz'); }
    public function canEditQuiz(): bool   { return $this->hasPermission('edit_quiz'); }
    public function canDeleteQuiz(): bool { return $this->hasPermission('delete_quiz'); }
    public function canManageQuiz(): bool { return $this->hasPermission('manage_quiz'); }

    /*
    |--------------------------------------------------------------------------
    | HELPERS — CATEGORY
    |--------------------------------------------------------------------------
    */

    public function canCreateCategory(): bool { return $this->hasPermission('create_category'); }
    public function canEditCategory(): bool   { return $this->hasPermission('edit_category'); }
    public function canDeleteCategory(): bool { return $this->hasPermission('delete_category'); }
    public function canManageCategory(): bool { return $this->hasPermission('manage_category'); }

    /*
    |--------------------------------------------------------------------------
    | HELPERS — USER
    |--------------------------------------------------------------------------
    */

    public function canCreateUser(): bool { return $this->hasPermission('create_user'); }
    public function canEditUser(): bool   { return $this->hasPermission('edit_user'); }
    public function canDeleteUser(): bool { return $this->hasPermission('delete_user'); }
    public function canManageUser(): bool { return $this->hasPermission('manage_user'); }
}
