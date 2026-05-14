<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class RolePermission extends Model
{
    protected $fillable = ['role', 'permission'];

    protected static function booted(): void
    {
        $clear = function (RolePermission $rp) {
            Cache::forget("role_perms_{$rp->role}");
        };

        static::saved($clear);
        static::deleted($clear);
    }
}
