<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuestionVersion extends Model
{
    // Nessun updated_at: le versioni sono immutabili dopo la creazione.
    const UPDATED_AT = null;

    protected $fillable = [
        'question_id',
        'version_number',
        'question',
        'is_true',
        'image',
        'category_id',
        'created_by',
        'created_at',
    ];

    protected $casts = [
        'is_true'        => 'boolean',
        'version_number' => 'integer',
        'category_id'    => 'integer',
        'created_by'     => 'integer',
        'created_at'     => 'datetime',
    ];

    /*
    |--------------------------------------------------------------------------
    | RELATIONS
    |--------------------------------------------------------------------------
    */

    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /*
    |--------------------------------------------------------------------------
    | SCOPES
    |--------------------------------------------------------------------------
    */

    public function scopeLatestVersion(Builder $query): Builder
    {
        return $query->orderByDesc('version_number');
    }
}
