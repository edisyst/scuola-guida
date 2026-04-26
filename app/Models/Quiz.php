<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Quiz extends Model
{
    public static function generateRandom($limit = 10)
    {
        return Question::inRandomOrder()
            ->limit($limit)
            ->get();
    }

    public function questions()
    {
        return $this->belongsToMany(Question::class);
    }
}
