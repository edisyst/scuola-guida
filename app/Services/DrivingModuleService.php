<?php

namespace App\Services;

use App\Models\DrivingModule;
use App\Models\LicenseType;
use Illuminate\Support\Collection;

class DrivingModuleService
{
    public function allForLicenseType(LicenseType $lt): Collection
    {
        return $lt->drivingModules()->ordered()->get();
    }

    public function allForSelect(LicenseType $lt): Collection
    {
        return $this->allForLicenseType($lt);
    }

    public function create(LicenseType $lt, array $data): DrivingModule
    {
        $data['license_type_id'] = $lt->id;
        return DrivingModule::create($data);
    }

    public function update(DrivingModule $module, array $data): DrivingModule
    {
        $module->update($data);
        return $module->fresh();
    }

    public function delete(DrivingModule $module): void
    {
        // Verifica vincolo prima che il DB lanci un'eccezione RESTRICT
        if ($module->drivingSessions()->exists()) {
            throw new \RuntimeException(__('driving.module_has_sessions'));
        }

        $module->delete();
    }
}
