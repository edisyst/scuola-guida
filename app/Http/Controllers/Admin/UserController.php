<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\AuditLog;
use App\Models\User;
use App\Services\GdprExportService;
use App\Services\UserService;

class UserController extends Controller
{
    public function __construct(private UserService $service) {}

    public function index(Request $request)
    {
        $licenseTypeId = $request->query('license_type_id');
        $users = User::with('activeLicenseType')
            ->when($licenseTypeId, fn ($q, $v) => $q->where('active_license_type_id', $v))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        $licenseTypes = app(LicenseTypeService::class)->allForSelect();

        return view('admin.users.index', compact('users', 'licenseTypes', 'licenseTypeId'));
    }

    public function create()
    {
        abort_unless(auth()->user()->canCreateUser(), 403);

        return view('admin.users.create', $this->permissionViewData());
    }

    public function store(StoreUserRequest $request)
    {
        $this->service->create($request->validated());

        return redirect()->route('admin.users.index')
            ->with('success', __('flash.user_created'));
    }

    public function edit(User $user)
    {
        abort_unless(auth()->user()->canEditUser(), 403);

        return view('admin.users.edit', array_merge(
            ['user' => $user],
            $this->permissionViewData()
        ));
    }

    public function update(UpdateUserRequest $request, User $user)
    {
        $this->service->update($user, $request->validated());

        return redirect()->route('admin.users.index')
            ->with('success', __('flash.user_updated'));
    }

    public function downloadPersonalData(User $user, GdprExportService $service): \Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        abort_unless(auth()->user()->canEditUser(), 403);

        $zipPath = $service->generateZip($user);

        AuditLog::create([
            'user_id'    => auth()->id(),
            'event'      => 'gdpr_export',
            'model_type' => User::class,
            'model_id'   => $user->id,
            'old_values' => [],
            'new_values' => ['exported_by' => auth()->id(), 'exported_at' => now()->toIso8601String()],
        ]);

        return response()
            ->download($zipPath, "dati-utente-{$user->id}.zip")
            ->deleteFileAfterSend(true);
    }

    public function destroy(User $user)
    {
        abort_unless(auth()->user()->canDeleteUser(), 403);

        if ($user->id === auth()->id()) {
            return back()->with('error', 'Non puoi eliminarti');
        }

        $user->delete();

        return back()->with('success', __('flash.user_deleted'));
    }

    private function permissionViewData(): array
    {
        return [
            'entities'     => User::ENTITIES,
            'actions'      => User::ACTIONS,
            'entityLabels' => User::LABELS,
            'actionLabels' => User::ACTION_LABELS,
            'roles'        => User::ROLES,
        ];
    }
}
