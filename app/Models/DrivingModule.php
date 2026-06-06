<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DrivingModule extends Model
{
    use HasFactory, Auditable;

    protected $fillable = [
        'license_type_id',
        'code',
        'name',
        'description',
        'required_hours',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'required_hours' => 'decimal:1',
            'sort_order'     => 'integer',
        ];
    }

    public function licenseType(): BelongsTo
    {
        return $this->belongsTo(LicenseType::class);
    }

    public function drivingSessions(): HasMany
    {
        return $this->hasMany(DrivingSession::class);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order');
    }
}
