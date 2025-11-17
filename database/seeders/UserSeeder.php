<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create the main admin user

        User::create([
            'name' => 'Jay',
            'email' => 'wjay.inclino@gmail.com',
            'password' => bcrypt('IamJay123'),
            'email_verified_at' => now(),
            'role' => UserRole::Admin,
        ]);

        // Create demo users for different business types

        // User 1: Fruits, Vegetables, and Rice Store
        User::create([
            'name' => 'Maria Santos',
            'email' => 'maria.santos@demo.com',
            'password' => bcrypt('password'),
            'email_verified_at' => now(),
        ]);

        // User 2: Mini Grocery Store
        User::create([
            'name' => 'Roberto Cruz',
            'email' => 'roberto.cruz@demo.com',
            'password' => bcrypt('password'),
            'email_verified_at' => now(),
        ]);

        // User 3: Sari-Sari Store
        User::create([
            'name' => 'Luz Reyes',
            'email' => 'luz.reyes@demo.com',
            'password' => bcrypt('password'),
            'email_verified_at' => now(),
        ]);
    }
}
