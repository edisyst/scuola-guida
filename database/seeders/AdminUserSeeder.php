<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        // Admin
        User::create([
            'name' => 'Admin',
            'email' => 'admin@admin.admin',
            'password' => Hash::make('admin'),
            'role' => User::ROLE_ADMIN,
//             'permissions' => [], // ROLE_ADMIN bypassa tutto
        ]);

        // Editor
        User::create([
            'name' => 'Editor',
            'email' => 'editor@editor.editor',
            'password' => Hash::make('editor'),
            'role' => User::ROLE_EDITOR,
            'permissions' => [
                'create_question',
                'edit_question',
            ],
        ]);

        // Viewer
        User::create([
            'name' => 'Viewer',
            'email' => 'viewer@viewer.viewer',
            'password' => Hash::make('viewer'),
            'role' => User::ROLE_VIEWER,
            'permissions' => [],
        ]);

        $this->command->info("CREATI UTENTI BASE");
    }
}
