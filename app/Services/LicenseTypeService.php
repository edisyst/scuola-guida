<?php

namespace App\Services;

use App\Models\LicenseType;
use Illuminate\Support\Collection;
use RuntimeException;

class LicenseTypeService
{
    public function all(): Collection
    {
        return LicenseType::active()
            ->orderBy('sort_order')
            ->get();
    }

    public function allForSelect(): Collection
    {
        return $this->all();
    }

    public function withCategories(): Collection
    {
        return LicenseType::active()
            ->whereHas('categories')
            ->orderBy('sort_order')
            ->get();
    }

    public function find(int $id): LicenseType
    {
        return LicenseType::findOrFail($id);
    }

    public function create(array $data): LicenseType
    {
        return LicenseType::create($data);
    }

    public function update(LicenseType $licenseType, array $data): LicenseType
    {
        $licenseType->update($data);

        return $licenseType->refresh();
    }

    public function syncCategories(LicenseType $licenseType, array $categoryIds): void
    {
        $licenseType->categories()->sync($categoryIds);
    }

    public function delete(LicenseType $licenseType): void
    {
        if ($licenseType->quizzes()->exists()) {
            throw new RuntimeException('Non è possibile eliminare un tipo di patente associato a quiz.');
        }

        $licenseType->delete();
    }
}
