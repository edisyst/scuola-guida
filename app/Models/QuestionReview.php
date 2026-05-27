<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuestionReview extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'question_id',
        'next_review_at',
        'interval_days',
        'ease_factor',
        'repetitions',
        'last_reviewed_at',
    ];

    protected function casts(): array
    {
        return [
            'next_review_at'   => 'datetime',
            'last_reviewed_at' => 'datetime',
            'ease_factor'      => 'decimal:2',
            'interval_days'    => 'integer',
            'repetitions'      => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class);
    }
}
