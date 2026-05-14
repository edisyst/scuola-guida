<?php

namespace App\Models;

use App\Services\UserStatsService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QuizAttempt extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'quiz_id',
        'score',
        'total_questions',
        'duration',
        'answers',
    ];

    protected $casts = [
        'answers' => 'array',
    ];

    protected static function booted(): void
    {
        $invalidate = function (QuizAttempt $attempt) {
            if ($attempt->user_id) {
                UserStatsService::forget($attempt->user_id);
            }

            if ($attempt->isDirty('user_id') && $attempt->getOriginal('user_id')) {
                UserStatsService::forget($attempt->getOriginal('user_id'));
            }
        };

        static::saved($invalidate);
        static::deleted($invalidate);
    }

    /*
    |--------------------------------------------------------------------------
    | RELATIONS
    |--------------------------------------------------------------------------
    */

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function quiz()
    {
        return $this->belongsTo(Quiz::class);
    }

    /*
    |--------------------------------------------------------------------------
    | HELPERS
    |--------------------------------------------------------------------------
    */

    // percentuale risultato
    public function getPercentageAttribute(): float
    {
        if ($this->total_questions === 0) {
            return 0;
        }

        return round(($this->score / $this->total_questions) * 100, 2);
    }

    // esito (pass/fail)
    public function getIsPassedAttribute(): bool
    {
        return $this->percentage >= 60;
    }
}
