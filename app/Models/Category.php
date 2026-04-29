<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Category extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
    ];

    protected static function booted()
    {
        static::saving(function ($category) {

            $baseSlug = Str::slug($category->name);
            $slug = $baseSlug;
            $counter = 1;

            while (
            static::where('slug', $slug)
                ->where('id', '!=', $category->id)
                ->exists()
            ) {
                $slug = $baseSlug . '-' . $counter++; // segnali → segnali-1 → segnali-2
            }

            $category->slug = $slug;
        });
    }

    /*
    |--------------------------------------------------------------------------
    | RELATIONS
    |--------------------------------------------------------------------------
    */

    public function questions()
    {
        return $this->hasMany(Question::class);
    }
}
