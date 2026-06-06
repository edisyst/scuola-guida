<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DrivingSession extends Model
{
    use HasFactory, Auditable;

    protected $fillable = [
        'student_id',
        'instructor_id',
        'driving_module_id',
        'conducted_at',
        'duration_minutes',
        'notes',
        'recorded_by',
    ];

    protected function casts(): array
    {
        return [
            'conducted_at'     => 'date',
            'duration_minutes' => 'integer',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function instructor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'instructor_id');
    }

    public function drivingModule(): BelongsTo
    {
        return $this->belongsTo(DrivingModule::class);
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}
