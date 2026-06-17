<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Str;

class Category extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'is_eu_directive',
    ];

    protected function casts(): array
    {
        return [
            'is_eu_directive' => 'boolean',
        ];
    }

    public function scopeEuDirective(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('is_eu_directive', true);
    }

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

    public function questions(): HasMany
    {
        return $this->hasMany(Question::class);
    }

    public function materials(): HasMany
    {
        return $this->hasMany(CategoryMaterial::class);
    }

    public function translations(): HasMany
    {
        return $this->hasMany(CategoryTranslation::class);
    }

    public function studyContents(): MorphMany
    {
        return $this->morphMany(StudyContent::class, 'studyable');
    }

    public function licenseTypes(): BelongsToMany
    {
        return $this->belongsToMany(LicenseType::class, 'category_license_type');
    }

    public function getLocalizedName(?string $locale = null): string
    {
        $locale ??= app()->getLocale();

        if ($locale === config('locales.default', 'it')) {
            return $this->name;
        }

        /** @var CategoryTranslation|null $translation */
        $translation = $this->translations->firstWhere('locale', $locale);

        return $translation?->name ?? $this->name;
    }
}
