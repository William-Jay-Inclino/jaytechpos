<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create admin user if it doesn't exist
        User::firstOrCreate(
            ['email' => 'admin@jaytechpos.com'],
            [
                'name' => 'System Administrator',
                'password' => Hash::make('admin123'),
                'role' => UserRole::Admin,
            ]
        );

        // Create a sample user
        User::firstOrCreate(
            ['email' => 'user@jaytechpos.com'],
            [
                'name' => 'Sample User',
                'password' => Hash::make('user123'),
                'role' => UserRole::User,
            ]
        );
    }
}
