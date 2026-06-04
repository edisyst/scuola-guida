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
        'mit_code',
        'mit_image_code',
    ];

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

    public function scopeFromMit($query)
    {
        return $query->whereNotNull('mit_code');
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

    public function versions(): HasMany
    {
        return $this->hasMany(QuestionVersion::class)->orderByDesc('version_number');
    }

    public function translations(): HasMany
    {
        return $this->hasMany(QuestionTranslation::class);
    }

    /*
    |--------------------------------------------------------------------------
    | LOCALIZZAZIONE TESTO (Feature 7.1)
    |--------------------------------------------------------------------------
    */

    /**
     * Testo della domanda nella lingua richiesta, con fallback all'italiano.
     *
     * Riusa la collection `translations` se già eager-loaded (zero query
     * aggiuntive). Null-safe in ogni passaggio: se la traduzione manca o è
     * vuota, ritorna sempre il testo originale italiano. Non lancia eccezioni.
     */
    public function getLocalizedText(string $locale): string
    {
        // Cerca la traduzione nella collection già caricata (zero query aggiuntive
        // se eager-loaded). Fallback al testo italiano originale se non esiste.
        $translation = $this->translations->firstWhere('locale', $locale);

        return $translation?->text ?: $this->question;
    }

    public function currentVersion(): ?QuestionVersion
    {
        return $this->versions()->first();
    }

    /**
     * Crea uno snapshot della domanda nel suo stato attuale (semantica "after":
     * la versione rappresenta lo stato corrente, non quello precedente).
     * Calcola version_number come max esistente + 1, o 1 se è la prima.
     */
    public function createVersion(): QuestionVersion
    {
        $maxVersion = $this->versions()->max('version_number') ?? 0;

        return $this->versions()->create([
            'version_number' => $maxVersion + 1,
            'question'       => $this->question,
            'is_true'        => $this->is_true,
            'image'          => $this->image,
            'category_id'    => $this->category_id,
            'created_by'     => auth()->check() ? auth()->id() : null,
            'created_at'     => now(),
        ]);
    }
}
