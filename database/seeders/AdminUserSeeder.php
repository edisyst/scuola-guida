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
            'email' => 'admin@test.com',
            'password' => Hash::make('password'),
            'role' => User::ROLE_ADMIN,
//             'permissions' => [], // ROLE_ADMIN bypassa tutto
        ]);

        // Editor
        User::create([
            'name' => 'Editor',
            'email' => 'editor@test.com',
            'password' => Hash::make('password'),
            'role' => User::ROLE_EDITOR,
            'permissions' => [
                'create_question',
                'edit_question',
            ],
        ]);

        // Viewer
        User::create([
            'name' => 'Viewer',
            'email' => 'viewer@test.com',
            'password' => Hash::make('password'),
            'role' => User::ROLE_VIEWER,
            'permissions' => [],
        ]);
    }
}
