<?php

namespace Database\Seeders;

use App\Models\RolePermission;
use App\Models\User;
use Illuminate\Database\Seeder;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        // read_xxx: tutti gli utenti autenticati (viewer, editor) — admin li riceve già via allPermissions()
        $readAll = [
            'read_question', 'read_quiz', 'read_category', 'read_user',
        ];

        foreach ($readAll as $perm) {
            RolePermission::firstOrCreate(['role' => User::ROLE_VIEWER, 'permission' => $perm]);
            RolePermission::firstOrCreate(['role' => User::ROLE_EDITOR, 'permission' => $perm]);
        }

        // Editor: scrittura su question/quiz/category (no delete, no user, no bulk)
        $editorOnly = [
            'create_question', 'edit_question',
            'create_quiz', 'edit_quiz',
            'create_category', 'edit_category',
        ];

        foreach ($editorOnly as $perm) {
            RolePermission::firstOrCreate(['role' => User::ROLE_EDITOR, 'permission' => $perm]);
        }

        // bulk_xxx: solo admin — già incluso in allPermissions(), nessuna riga nel DB necessaria

        $this->command->info("CREATI I RUOLI E I PERMESSI");
    }
}
