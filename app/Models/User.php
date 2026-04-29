<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    const ROLE_ADMIN = 'admin';
    const ROLE_EDITOR = 'editor';
    const ROLE_VIEWER = 'viewer';

    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'permissions' => 'array',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

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

    public function hasPermission(string $permission): bool
    {
        // 🔥 admin bypass totale
        if ($this->isAdmin()) {
            return true;
        }

        $permissions = $this->permissions ?? [];

        // 🔥 gestione permesso globale
        if (in_array('manage_question', $permissions)) {
            return true;
        }

        return in_array($permission, $permissions);
    }

    public function canCreateQuestion(): bool
    {
        return $this->hasPermission('create_question');
    }

    public function canEditQuestion(): bool
    {
        return $this->hasPermission('edit_question');
    }

    public function canDeleteQuestion(): bool
    {
        return $this->hasPermission('delete_question');
    }
}
