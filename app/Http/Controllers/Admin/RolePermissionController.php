<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\RolePermission;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class RolePermissionController extends Controller
{
    public function index()
    {
        $matrix = $this->buildMatrix();

        return view('admin.roles.index', [
            'matrix'       => $matrix,
            'entities'     => User::ENTITIES,
            'actions'      => User::ACTIONS,
            'entityLabels' => User::LABELS,
            'actionLabels' => User::ACTION_LABELS,
            'roles'        => User::ROLES,
            'adminRole'    => User::ROLE_ADMIN,
        ]);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'matrix'             => 'nullable|array',
            'matrix.*'           => 'array',
            'matrix.*.*'         => 'in:1,0,true,false',
        ]);

        $matrix = $data['matrix'] ?? [];
        $allPerms = User::allPermissions();
        $editableRoles = array_filter(
            array_keys(User::ROLES),
            fn($r) => $r !== User::ROLE_ADMIN
        );

        DB::transaction(function () use ($matrix, $allPerms, $editableRoles) {

            foreach ($editableRoles as $role) {

                $checked = collect($matrix[$role] ?? [])
                    ->filter(fn($v) => in_array($v, ['1', 'true', 1, true], true))
                    ->keys()
                    ->filter(fn($p) => in_array($p, $allPerms, true))
                    ->values()
                    ->all();

                RolePermission::where('role', $role)->delete();

                foreach ($checked as $permission) {
                    RolePermission::create([
                        'role'       => $role,
                        'permission' => $permission,
                    ]);
                }

                Cache::forget("role_perms_{$role}");
            }
        });

        return redirect()->route('admin.roles.index')
            ->with('success', 'Permessi dei ruoli aggiornati');
    }

    /**
     * matrix[role][permission] = bool
     */
    private function buildMatrix(): array
    {
        $all = RolePermission::all()
            ->groupBy('role')
            ->map(fn($items) => $items->pluck('permission')->toArray());

        $matrix = [];

        foreach (array_keys(User::ROLES) as $role) {
            $rolePerms = $role === User::ROLE_ADMIN
                ? User::allPermissions()
                : ($all->get($role, []));

            foreach (User::allPermissions() as $perm) {
                $matrix[$role][$perm] = in_array($perm, $rolePerms, true);
            }
        }

        return $matrix;
    }
}
