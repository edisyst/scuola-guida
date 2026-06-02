<?php

namespace App\Observers;

use App\Models\InstructorNote;

class InstructorNoteObserver
{
    public function creating(InstructorNote $note): void
    {
        if (auth()->check() && $note->created_by === null) {
            $note->created_by = auth()->id();
        }
    }
}
