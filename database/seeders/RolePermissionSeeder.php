<?php

namespace Database\Seeders;

use App\Models\RolePermission;
use App\Models\User;
use Illuminate\Database\Seeder;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Editor: scrittura su question/quiz/category (no delete, no user)
        $editor = [
            'create_question', 'edit_question',
            'create_quiz', 'edit_quiz',
            'create_category', 'edit_category',
        ];

        foreach ($editor as $perm) {
            RolePermission::firstOrCreate([
                'role' => User::ROLE_EDITOR,
                'permission' => $perm,
            ]);
        }

        // Viewer: nessun permesso di scrittura (sola lettura)
    }
}
