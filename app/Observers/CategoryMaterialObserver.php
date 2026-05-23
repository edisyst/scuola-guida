<?php

namespace App\Observers;

use App\Models\CategoryMaterial;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class CategoryMaterialObserver
{
    public function creating(CategoryMaterial $material): void
    {
        if (!$material->created_by) {
            $material->created_by = Auth::id();
        }
    }

    public function deleting(CategoryMaterial $material): void
    {
        if ($material->type === 'pdf' && $material->url_or_path) {
            Storage::disk('public')->delete($material->url_or_path);
        }
    }
}
