<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserBadge extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'badge_code',
        'earned_at',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'earned_at' => 'datetime',
            'metadata'  => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function config(): array
    {
        return config('badges.' . $this->badge_code, []);
    }
}
