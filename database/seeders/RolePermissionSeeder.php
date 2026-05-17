<?php

namespace Database\Seeders;

use App\Models\RolePermission;
use App\Models\User;
use Illuminate\Database\Seeder;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Default sensati: viewer/editor leggono tutto, editor scrive su entità di contenuto.
        // Admin riceve sempre tutti i permessi via allPermissions(): nessuna riga in DB.
        // bulk_* e manage_* restano off di default — configurabili da /admin/roles.

        $readAll = [
            'read_question', 'read_quiz', 'read_category', 'read_user',
        ];

        foreach ($readAll as $perm) {
            RolePermission::firstOrCreate(['role' => User::ROLE_VIEWER, 'permission' => $perm]);
            RolePermission::firstOrCreate(['role' => User::ROLE_EDITOR, 'permission' => $perm]);
        }

        $editorOnly = [
            'create_question', 'edit_question',
            'create_quiz', 'edit_quiz',
            'create_category', 'edit_category',
        ];

        foreach ($editorOnly as $perm) {
            RolePermission::firstOrCreate(['role' => User::ROLE_EDITOR, 'permission' => $perm]);
        }

        $this->command->info("CREATI I RUOLI E I PERMESSI");
    }
}
