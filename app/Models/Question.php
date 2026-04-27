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

    public function scopeCategory($query, $categoryId)
    {
        if ($categoryId) {
            $query->where('category_id', $categoryId);
        }
    }

    public function scopeSearch($query, $search)
    {
        if ($search) {
            $query->where('question', 'like', "%{$search}%");
        }
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function quizzes()
    {
        return $this->belongsToMany(Quiz::class);
    }
}
