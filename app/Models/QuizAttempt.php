<?php

namespace App\Models;

use App\Services\UserStatsService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class QuizAttempt extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'quiz_id',
        'quiz_enrollment_id',
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
        // withDefault() evita NPE nelle view condivise quando quiz_id è null
        // (tentativi del simulatore, che non sono legati a un Quiz preesistente).
        return $this->belongsTo(Quiz::class)->withDefault([
            'title' => 'Simulatore Esame',
        ]);
    }

    public function enrollment()
    {
        return $this->belongsTo(QuizEnrollment::class, 'quiz_enrollment_id');
    }

    /*
    |--------------------------------------------------------------------------
    | HELPERS
    |--------------------------------------------------------------------------
    */

    /**
     * Restituisce il risultato (0|1) per una singola risposta, gestendo sia il
     * formato esteso { correct: 1, ... } sia il formato flat legacy { id: 1 }.
     */
    public function getAnswerResult(int|string $questionId): ?int
    {
        $answers = $this->answers ?? [];
        $entry   = $answers[$questionId] ?? null;

        if (is_null($entry))  return null;
        if (is_array($entry)) return (int) ($entry['correct'] ?? 0);
        return (int) $entry;
    }

    /** Restituisce il timestamp Carbon di risposta, o null per formato flat o campo assente. */
    public function getAnsweredAt(int|string $questionId): ?Carbon
    {
        $entry = ($this->answers ?? [])[$questionId] ?? null;

        if (!is_array($entry) || empty($entry['answered_at'])) return null;
        return Carbon::createFromTimestamp((int) $entry['answered_at']);
    }

    /** Restituisce i secondi impiegati sulla domanda, o null per formato flat o campo assente. */
    public function getTimeSpent(int|string $questionId): ?int
    {
        $entry = ($this->answers ?? [])[$questionId] ?? null;

        if (!is_array($entry) || !array_key_exists('time_spent_seconds', $entry)) return null;
        return $entry['time_spent_seconds'] !== null ? (int) $entry['time_spent_seconds'] : null;
    }

    /** Restituisce la posizione progressiva della risposta nella sessione, o null per formato flat. */
    public function getAnswerPosition(int|string $questionId): ?int
    {
        $entry = ($this->answers ?? [])[$questionId] ?? null;

        if (!is_array($entry) || !array_key_exists('position', $entry)) return null;
        return $entry['position'] !== null ? (int) $entry['position'] : null;
    }

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
