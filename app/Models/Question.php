<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Traits\Auditable;

class Question extends Model
{
    use HasFactory, Auditable;

    protected $fillable = [
        'category_id',
        'question',
        'is_true',
        'image',
    ];

    protected $with = ['category']; // carica sempre category automaticamente (usare solo se serve sempre)

    /*
    |--------------------------------------------------------------------------
    | SCOPES
    |--------------------------------------------------------------------------
    */

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

    /*
    |--------------------------------------------------------------------------
    | RELATIONS
    |--------------------------------------------------------------------------
    */

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function quizzes()
    {
        return $this->belongsToMany(Quiz::class);
    }

    public function bookmarkedBy(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'question_user_bookmarks')
                    ->withPivot('note')
                    ->withTimestamps();
    }

    public function reports(): HasMany
    {
        return $this->hasMany(QuestionReport::class);
    }

    public function pendingReports(): HasMany
    {
        return $this->hasMany(QuestionReport::class)->pending();
    }
}
