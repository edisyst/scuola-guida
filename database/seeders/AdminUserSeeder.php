<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        User::create([
            'name' => 'Admin',
            'email' => 'admin@test.com',
            'password' => Hash::make('password'),
            'role' => User::ROLE_ADMIN, // 🔥 FIX
        ]);

        User::create([
            'name' => 'Editor',
            'email' => 'editor@test.com',
            'password' => Hash::make('password'),
            'role' => User::ROLE_EDITOR,
        ]);

        User::create([
            'name' => 'Viewer',
            'email' => 'viewer@test.com',
            'password' => Hash::make('password'),
            'role' => User::ROLE_VIEWER,
        ]);
    }
}
