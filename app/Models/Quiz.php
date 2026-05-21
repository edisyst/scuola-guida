<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Quiz extends Model
{
    use HasFactory;

    public const STATUS_DRAFT     = 'draft';
    public const STATUS_PUBLISHED = 'published';
    public const STATUS_CONFIRMED = 'confirmed';

    public const STATUSES = [
        self::STATUS_DRAFT     => 'Bozza',
        self::STATUS_PUBLISHED => 'Pubblicato',
        self::STATUS_CONFIRMED => 'Confermato',
    ];

    protected $fillable = [
        'title',
        'status',
        'confirmed_at',
        'confirmed_by',
        'max_questions',
        'time_limit',
        'max_errors',
        'enrollments_open_at',
        'enrollments_close_at',
    ];

    protected $casts = [
        'max_questions'        => 'integer',
        'confirmed_at'         => 'datetime',
        'enrollments_open_at'  => 'datetime',
        'enrollments_close_at' => 'datetime',
    ];

    /*
    |--------------------------------------------------------------------------
    | HELPERS
    |--------------------------------------------------------------------------
    */

    public function hasQuestion($questionId)
    {
        return $this->questions()->where('question_id', $questionId)->exists();
    }

    public function hasReachedLimit(): bool
    {
        return $this->questions()->count() >= $this->max_questions;
    }

    public function isDraft(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

    public function isPublished(): bool
    {
        return $this->status === self::STATUS_PUBLISHED;
    }

    public function isConfirmed(): bool
    {
        return $this->status === self::STATUS_CONFIRMED;
    }

    public function isLocked(): bool
    {
        return $this->isConfirmed();
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    /*
    |--------------------------------------------------------------------------
    | ENROLLMENT SCHEDULING
    |--------------------------------------------------------------------------
    */

    public function enrollmentsNotYetOpen(): bool
    {
        return $this->enrollments_open_at && $this->enrollments_open_at->isFuture();
    }

    public function enrollmentsClosed(): bool
    {
        return $this->enrollments_close_at && $this->enrollments_close_at->isPast();
    }

    /**
     * True quando esiste una finestra di schedulazione attiva e siamo dentro.
     * False sia se non c'è schedulazione (comportamento attuale) sia se
     * la finestra è chiusa o non ancora aperta.
     */
    public function enrollmentsCurrentlyOpen(): bool
    {
        return !$this->enrollmentsNotYetOpen() && !$this->enrollmentsClosed();
    }

    /*
    |--------------------------------------------------------------------------
    | SCOPES
    |--------------------------------------------------------------------------
    */

    public function scopeDraft($query)
    {
        return $query->where('status', self::STATUS_DRAFT);
    }

    public function scopePublished($query)
    {
        return $query->where('status', self::STATUS_PUBLISHED);
    }

    public function scopeConfirmed($query)
    {
        return $query->where('status', self::STATUS_CONFIRMED);
    }

    public function scopeEnrollmentsOpen(Builder $query): Builder
    {
        return $query->where(function ($q) {
            $q->whereNull('enrollments_open_at')
              ->orWhere('enrollments_open_at', '<=', now());
        })->where(function ($q) {
            $q->whereNull('enrollments_close_at')
              ->orWhere('enrollments_close_at', '>', now());
        });
    }

    public function scopeEnrollmentsUpcoming(Builder $query): Builder
    {
        return $query->whereNotNull('enrollments_open_at')
                     ->where('enrollments_open_at', '>', now());
    }

    public function scopeEnrollmentsClosed(Builder $query): Builder
    {
        return $query->whereNotNull('enrollments_close_at')
                     ->where('enrollments_close_at', '<=', now());
    }

    public function getEnrollmentStatusAttribute(): string
    {
        if ($this->enrollments_open_at === null && $this->enrollments_close_at === null) {
            return 'not_scheduled';
        }
        if ($this->enrollments_close_at && $this->enrollments_close_at->isPast()) {
            return 'closed';
        }
        if ($this->enrollments_open_at && $this->enrollments_open_at->isFuture()) {
            return 'upcoming';
        }
        return 'open';
    }

    /*
    |--------------------------------------------------------------------------
    | RELATIONS
    |--------------------------------------------------------------------------
    */

    public function questions()
    {
        return $this->belongsToMany(Question::class)
            ->withPivot('order')
            ->orderBy('question_quiz.order')
            ->withTimestamps();
    }

    public function attempts()
    {
        return $this->hasMany(QuizAttempt::class);
    }

    public function enrollments()
    {
        return $this->hasMany(QuizEnrollment::class);
    }

    public function confirmedBy()
    {
        return $this->belongsTo(User::class, 'confirmed_by');
    }
}
