<?php

namespace App\Observers;

use App\Models\User;

class UserObserver
{
    public function saved(User $user): void
    {
        clearAdminBadgesCache();
    }

    public function deleted(User $user): void
    {
        clearAdminBadgesCache();
    }
}
