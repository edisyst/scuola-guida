<?php

namespace App\Services;

use App\Models\RolePermission;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class RolePermissionService
{
    /**
     * Matrice [role][permission] = bool per la UI.
     * Contiene solo le MANAGED_ACTIONS (esclude read e bulk, che sono hardcoded).
     */
    public function buildMatrix(): array
    {
        $all = RolePermission::all()
            ->groupBy('role')
            ->map(fn ($items) => $items->pluck('permission')->toArray());

        $matrix = [];

        foreach (array_keys(User::ROLES) as $role) {
            $rolePerms = $role === User::ROLE_ADMIN
                ? User::managedPermissions()
                : ($all->get($role, []));

            foreach (User::managedPermissions() as $perm) {
                $matrix[$role][$perm] = in_array($perm, $rolePerms, true);
            }
        }

        return $matrix;
    }

    /**
     * Sincronizza la matrice salvata.
     * Ignora il ruolo admin e permette solo MANAGED_ACTIONS.
     */
    public function syncMatrix(array $matrix): void
    {
        $managedPerms = User::managedPermissions();
        $editableRoles = array_filter(
            array_keys(User::ROLES),
            fn ($r) => $r !== User::ROLE_ADMIN
        );

        DB::transaction(function () use ($matrix, $managedPerms, $editableRoles) {

            foreach ($editableRoles as $role) {

                $checked = collect($matrix[$role] ?? [])
                    ->filter(fn ($v) => in_array($v, ['1', 'true', 1, true], true))
                    ->keys()
                    ->filter(fn ($p) => in_array($p, $managedPerms, true))
                    ->values()
                    ->all();

                // Delete + re-insert intenzionale: rimuove i permessi deselezionati.
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
    }
}
