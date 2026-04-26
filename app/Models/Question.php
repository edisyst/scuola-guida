<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Question extends Model
{
    use HasFactory;

    protected $fillable = [
        'category_id',
        'question',
        'is_true',
        'image',
    ];

    protected $with = ['category']; // carica sempre category automaticamente (usare solo se serve sempre)

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function quizzes()
    {
        return $this->belongsToMany(Quiz::class);
    }
}
