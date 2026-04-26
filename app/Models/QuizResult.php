<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class QuizResult extends Model
{
    protected $fillable = [
        'user_id',
        'score',
        'total',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
