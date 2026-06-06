<?php

namespace App\Observers;

use App\Models\LicenseType;
use App\Models\User;

class LicenseTypeObserver
{
    public function deleting(LicenseType $licenseType): void
    {
        User::where('active_license_type_id', $licenseType->id)
            ->update(['active_license_type_id' => null]);
    }
}
