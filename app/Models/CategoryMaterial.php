<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class CategoryMaterial extends Model
{
    use HasFactory, Auditable;

    protected $fillable = [
        'category_id',
        'type',
        'title',
        'url_or_path',
        'content',
        'position',
    ];

    protected $casts = [
        'type'     => 'string',
        'position' => 'integer',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('position');
    }

    public function getEmbedUrlAttribute(): ?string
    {
        if ($this->type !== 'link' || !$this->url_or_path) {
            return null;
        }

        $url = $this->url_or_path;

        // youtube.com/watch?v=ID
        if (preg_match('/youtube\.com\/watch\?.*v=([A-Za-z0-9_\-]+)/i', $url, $m)) {
            return 'https://www.youtube.com/embed/' . $m[1];
        }

        // youtu.be/ID
        if (preg_match('/youtu\.be\/([A-Za-z0-9_\-]+)/i', $url, $m)) {
            return 'https://www.youtube.com/embed/' . $m[1];
        }

        return null;
    }

    public function getDownloadUrlAttribute(): ?string
    {
        if ($this->type !== 'pdf' || !$this->url_or_path) {
            return null;
        }

        return Storage::url($this->url_or_path);
    }
}
