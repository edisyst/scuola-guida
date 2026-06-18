<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreDrivingModuleRequest;
use App\Http\Requests\UpdateDrivingModuleRequest;
use App\Models\DrivingModule;
use App\Models\LicenseType;
use App\Services\DrivingModuleService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DrivingModuleController extends Controller
{
    public function __construct(private readonly DrivingModuleService $service) {}

    public function index(Request $request): View
    {
        abort_unless(auth()->user()->canManageDrivingModules(), 403);
        abort_if(!feature('driving_practice_enabled'), 404);

        $licenseTypes = LicenseType::active()->orderBy('sort_order')->get();

        // Filtra per tipo di patente se specificato in query string
        $query = DrivingModule::with('licenseType')->ordered();

        if ($licenseTypeId = $request->integer('license_type_id') ?: null) {
            $query->where('license_type_id', $licenseTypeId);
        }

        $modules = $query->get();

        return view('admin.driving-modules.index', compact('licenseTypes', 'modules'));
    }

    public function create(Request $request): View
    {
        abort_unless(auth()->user()->canManageDrivingModules(), 403);

        $licenseTypes        = LicenseType::active()->orderBy('sort_order')->get();
        $selectedLicenseType = $request->integer('license_type_id') ?: null;

        return view('admin.driving-modules.create', compact('licenseTypes', 'selectedLicenseType'));
    }

    public function store(StoreDrivingModuleRequest $request): RedirectResponse
    {
        abort_unless(auth()->user()->canManageDrivingModules(), 403);

        $lt = LicenseType::findOrFail($request->validated()['license_type_id']);
        $this->service->create($lt, $request->validated());

        return redirect()
            ->route('admin.driving-modules.index')
            ->with('success', __('flash.driving_module_created'));
    }

    public function show(DrivingModule $drivingModule): View
    {
        $module = $drivingModule->load(['licenseType', 'drivingSessions']);

        return view('admin.driving-modules.show', compact('module'));
    }

    public function edit(DrivingModule $drivingModule): View
    {
        abort_unless(auth()->user()->canManageDrivingModules(), 403);

        $module = $drivingModule->load('licenseType');

        return view('admin.driving-modules.edit', compact('module'));
    }

    public function update(UpdateDrivingModuleRequest $request, DrivingModule $drivingModule): RedirectResponse
    {
        abort_unless(auth()->user()->canManageDrivingModules(), 403);

        $this->service->update($drivingModule, $request->validated());

        return redirect()
            ->route('admin.driving-modules.index')
            ->with('success', __('flash.driving_module_updated'));
    }

    public function destroy(DrivingModule $drivingModule): RedirectResponse
    {
        abort_unless(auth()->user()->canManageDrivingModules(), 403);

        try {
            $this->service->delete($drivingModule);
            return redirect()
                ->route('admin.driving-modules.index')
                ->with('success', __('flash.driving_module_deleted'));
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}
