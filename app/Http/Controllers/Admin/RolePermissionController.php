<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateRolePermissionRequest;
use App\Models\User;
use App\Services\RolePermissionService;

class RolePermissionController extends Controller
{
    public function __construct(private RolePermissionService $service) {}

    public function index()
    {
        return view('admin.roles.index', [
            'matrix'       => $this->service->buildMatrix(),
            'entities'     => User::ENTITIES,
            'actions'      => User::MANAGED_ACTIONS,
            'entityLabels' => User::LABELS,
            'actionLabels' => User::MANAGED_ACTION_LABELS,
            'roles'        => User::ROLES,
            'adminRole'    => User::ROLE_ADMIN,
        ]);
    }

    public function update(UpdateRolePermissionRequest $request)
    {
        $this->service->syncMatrix($request->validated('matrix') ?? []);

        return redirect()->route('admin.roles.index')
            ->with('success', __('flash.permissions_saved'));
    }
}
