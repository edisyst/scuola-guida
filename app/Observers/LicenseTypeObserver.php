<?php

namespace App\Observers;

use App\Models\LicenseType;

class LicenseTypeObserver
{
    public function updated(LicenseType $licenseType): void
    {
    }

    public function deleted(LicenseType $licenseType): void
    {
    }
}
