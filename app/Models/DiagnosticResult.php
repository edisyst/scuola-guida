<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DiagnosticResult extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'category_id',
        'correct',
        'taken_at',
        'batch_id',
    ];

    protected $casts = [
        'correct'  => 'boolean',
        'taken_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }
}
