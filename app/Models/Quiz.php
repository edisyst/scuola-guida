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
    ];

    public static function generateRandom($limit = 10)
    {
        return Question::inRandomOrder()
            ->limit($limit)
            ->get();
    }

    public function hasQuestion($questionId)
    {
        return $this->questions()->where('question_id', $questionId)->exists();
    }

    public function questions()
    {
        return $this->belongsToMany(Question::class);
    }

    public function attempts()
    {
        return $this->hasMany(QuizAttempt::class);
    }
}
