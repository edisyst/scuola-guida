<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class StudyContent extends Model
{
    use HasFactory, Auditable;

    protected $fillable = [
        'studyable_type',
        'studyable_id',
        'title',
        'body',
        'is_published',
        'order',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'is_published' => 'boolean',
            'order'        => 'integer',
        ];
    }

    public function studyable(): MorphTo
    {
        return $this->morphTo();
    }

    public function readers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'study_content_user')
                    ->withPivot('read_at');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('order');
    }

    public function isReadBy(User $user): bool
    {
        return $this->readers()
                    ->where('user_id', $user->id)
                    ->whereNotNull('study_content_user.read_at')
                    ->exists();
    }
}
