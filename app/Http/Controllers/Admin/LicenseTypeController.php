<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreLicenseTypeRequest;
use App\Http\Requests\UpdateLicenseTypeRequest;
use App\Models\Category;
use App\Models\LicenseType;
use App\Services\LicenseTypeService;
use Illuminate\Http\Request;

class LicenseTypeController extends Controller
{
    public function __construct(private LicenseTypeService $service) {}

    public function index()
    {
        abort_unless(auth()->user()->canEditLicenseType(), 403);

        $licenseTypes = LicenseType::with('categories', 'quizzes')
            ->orderBy('sort_order')
            ->paginate(10);

        return view('admin.license-types.index', compact('licenseTypes'));
    }

    public function create()
    {
        abort_unless(auth()->user()->canEditLicenseType(), 403);

        $categories = Category::orderBy('name')->get();

        return view('admin.license-types.create', compact('categories'));
    }

    public function store(StoreLicenseTypeRequest $request)
    {
        $data = $request->validated();
        $categoryIds = $data['category_ids'] ?? [];
        unset($data['category_ids']);

        $licenseType = $this->service->create($data);
        $this->service->syncCategories($licenseType, $categoryIds);

        return redirect()->route('admin.license-types.index')
            ->with('success', __('flash.license_type_created'));
    }

    public function edit(LicenseType $licenseType)
    {
        abort_unless(auth()->user()->canEditLicenseType(), 403);

        $categories = Category::orderBy('name')->get();
        $selectedCategoryIds = $licenseType->categories()->pluck('categories.id')->toArray();

        return view('admin.license-types.edit', compact('licenseType', 'categories', 'selectedCategoryIds'));
    }

    public function update(UpdateLicenseTypeRequest $request, LicenseType $licenseType)
    {
        $data = $request->validated();
        $categoryIds = $data['category_ids'] ?? [];
        unset($data['category_ids']);

        $this->service->update($licenseType, $data);
        $this->service->syncCategories($licenseType, $categoryIds);

        return redirect()->route('admin.license-types.index')
            ->with('success', __('flash.license_type_updated'));
    }

    public function destroy(LicenseType $licenseType)
    {
        abort_unless(auth()->user()->canEditLicenseType(), 403);

        try {
            $this->service->delete($licenseType);
            return back()->with('success', __('flash.license_type_deleted'));
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function syncCategories(Request $request, LicenseType $licenseType)
    {
        abort_unless(auth()->user()->canEditLicenseType(), 403);

        $categoryIds = $request->input('category_ids', []);
        $this->service->syncCategories($licenseType, $categoryIds);

        return redirect()->route('admin.license-types.edit', $licenseType)
            ->with('success', __('flash.categories_synced'));
    }
}
