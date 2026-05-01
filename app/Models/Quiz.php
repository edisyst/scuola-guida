<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Quiz extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'is_active',
        'max_questions',
    ];

    protected $casts = [
        'max_questions' => 'integer',
    ];

    public static function generateRandom($limit = 10)
    {
        return Question::inRandomOrder()
            ->limit($limit)
            ->get();
    }

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

    public function remainingSlots(): int
    {
        return $this->max_questions - $this->questions()->count();
    }

//    public function getMaxQuestionsAttribute($value)
//    {
//        return $value ?? 30;
//    }

    /*
    |--------------------------------------------------------------------------
    | RELATIONS
    |--------------------------------------------------------------------------
    */

    public function questions()
    {
        return $this->belongsToMany(Question::class);
    }

    public function attempts()
    {
        return $this->hasMany(QuizAttempt::class);
    }
}
