<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuestionReport extends Model
{
    use HasFactory;

    public const STATUS_PENDING  = 'pending';
    public const STATUS_ACCEPTED = 'accepted';
    public const STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'question_id',
        'user_id',
        'body',
        'type',
        'status',
        'admin_note',
        'resolved_by',
        'resolved_at',
    ];

    protected $casts = [
        'resolved_at' => 'datetime',
        'status'      => 'string',
    ];

    public static function types(): array
    {
        return [
            'risposta_errata'    => 'La risposta corretta sembra sbagliata',
            'testo_ambiguo'      => 'Testo della domanda ambiguo o poco chiaro',
            'immagine_mancante'  => 'Immagine assente o non visibile',
            'contenuto_obsoleto' => 'Contenuto obsoleto (norma cambiata)',
            'altro'              => 'Altro problema',
        ];
    }

    public static function statuses(): array
    {
        return [
            self::STATUS_PENDING  => 'In attesa',
            self::STATUS_ACCEPTED => 'Accettata',
            self::STATUS_REJECTED => 'Rifiutata',
        ];
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function resolvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeAccepted(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_ACCEPTED);
    }

    public function scopeRejected(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_REJECTED);
    }
}
