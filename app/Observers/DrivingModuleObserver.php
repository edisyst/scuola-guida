<?php

namespace App\Observers;

use App\Models\DrivingModule;

class DrivingModuleObserver
{
    public function deleting(DrivingModule $drivingModule): void
    {
        \App\Models\StudyContent::where('studyable_type', DrivingModule::class)
                                ->where('studyable_id', $drivingModule->id)
                                ->each(fn ($c) => $c->delete());
    }
}
