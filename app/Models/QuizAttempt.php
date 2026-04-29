<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QuizAttempt extends Model
{
    protected $fillable = [
        'user_id',
        'quiz_id',
        'score',
        'total_questions',
        'duration',
    ];

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
