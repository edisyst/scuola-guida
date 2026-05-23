<?php

namespace App\Services;

use App\Models\Category;
use App\Models\CategoryMaterial;
use Illuminate\Http\UploadedFile;

class CategoryMaterialService
{
    public function create(Category $category, array $data, ?UploadedFile $file = null): CategoryMaterial
    {
        $data['category_id'] = $category->id;
        $data['position']    = $this->nextPosition($category->id);

        if ($data['type'] === 'pdf' && $file) {
            $data['url_or_path'] = $file->store("materials/{$category->id}", 'public');
        }

        return CategoryMaterial::create($data);
    }

    public function update(CategoryMaterial $material, array $data, ?UploadedFile $file = null): CategoryMaterial
    {
        if ($data['type'] === 'pdf' && $file) {
            if ($material->url_or_path) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($material->url_or_path);
            }
            $data['url_or_path'] = $file->store("materials/{$material->category_id}", 'public');
        }

        $material->update($data);

        return $material;
    }

    public function delete(CategoryMaterial $material): void
    {
        // Observer handles physical file deletion
        $material->delete();
    }

    public function reorder(array $orderedIds): void
    {
        foreach ($orderedIds as $position => $id) {
            CategoryMaterial::where('id', $id)->update(['position' => $position]);
        }
    }

    private function nextPosition(int $categoryId): int
    {
        $max = CategoryMaterial::where('category_id', $categoryId)->max('position');

        return $max === null ? 0 : $max + 1;
    }
}
